<?php

namespace App\Services;

use App\Helpers\ActivityLogHelper;
use App\Helpers\NotificationHelper;
use App\Models\KpiCorrectionRequest as Correction;
use App\Models\Province;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// The correction request flow for a locked period — a quarter after its
// deadline, or a year's targets after the Q4 deadline:
//   1. the provincial admin files it, with a reason      → pending_regional
//   2. the regional director forwards it, or returns it  → pending_central | returned
//      (a returned request is fixed and resubmitted      → pending_regional)
//   3. the super admin approves it until a date they     → approved
//      choose, or declines it with a reason              → declined
// An approved request reopens only that period, only for that province, through
// the end of the chosen day (KpiEntryService::isQuarterOpen). There is no other
// way to unlock a period. Everyone involved is notified at each step.
class KpiCorrectionService
{
    public function __construct(private KpiEntryService $entries) {}

    // ── Steps ────────────────────────────────────────────────────────────────

    public function file(User $by, int $provinceId, int $year, ?int $quarter, string $reason): Correction
    {
        $label = $quarter ? "Q{$quarter} {$year}" : "{$year} targets";
        $open  = $quarter
            ? $this->entries->isQuarterOpen($provinceId, $year, $quarter)
            : $this->entries->areTargetsOpen($provinceId, $year);
        if ($open) {
            throw ValidationException::withMessages(['period' => "{$label} is still open, so it can be changed directly on the KPI Entry page."]);
        }
        if (Correction::forPeriod($provinceId, $year, $quarter)->active()->exists()) {
            throw ValidationException::withMessages(['period' => "A correction request for {$label} is already in progress."]);
        }

        $correction = (new Correction([
            'province_id'  => $provinceId,
            'year'         => $year,
            'quarter'      => $quarter,
            'reason'       => $reason,
            'requested_by' => $by->id,
            'status'       => Correction::PENDING_REGIONAL,
        ]))->addStep('requested', $by, $reason);
        $correction->save();

        $province = $correction->province;
        $this->notify($by, $this->regionalDirectors($province), $correction,
            "{$province->name} requested a correction to {$label}: \"" . Str::limit($reason, 120) . '"',
            '/kpi-corrections');
        ActivityLogHelper::kpiCorrection('requested', $province->name, $label, $reason);

        return $correction;
    }

    public function resubmit(Correction $correction, User $by, string $reason): void
    {
        $this->expectStatus($correction, Correction::RETURNED);
        $correction->fill(['reason' => $reason, 'status' => Correction::PENDING_REGIONAL])
            ->addStep('resubmitted', $by, $reason)->save();

        $province = $correction->province;
        $this->notify($by, $this->regionalDirectors($province), $correction,
            "{$province->name} resubmitted its correction request for {$correction->periodLabel()}.",
            '/kpi-corrections');
        ActivityLogHelper::kpiCorrection('resubmitted', $province->name, $correction->periodLabel(), $reason);
    }

    public function forward(Correction $correction, User $director, ?string $note): void
    {
        $this->expectStatus($correction, Correction::PENDING_REGIONAL);
        $this->assertOwnRegion($correction, $director);

        $correction->fill([
            'status'               => Correction::PENDING_CENTRAL,
            'regional_reviewer_id' => $director->id,
            'regional_reviewed_at' => now(),
            'regional_note'        => $note,
        ])->addStep('forwarded', $director, $note)->save();

        $province = $correction->province;
        $label    = $correction->periodLabel();
        $this->notify($director, User::whereRole('super_admin')->get(), $correction,
            "{$province->region?->name} forwarded {$province->name}'s correction request for {$label}.",
            '/kpi-corrections');
        $this->notify($director, $this->provinceTeam($province, false), $correction,
            "Your correction request for {$label} was forwarded to the central office.",
            "/provincial-kpi/{$correction->year}");
        ActivityLogHelper::kpiCorrection('forwarded', $province->name, $label, (string) $note);
    }

    public function returnToProvince(Correction $correction, User $director, string $note): void
    {
        $this->expectStatus($correction, Correction::PENDING_REGIONAL);
        $this->assertOwnRegion($correction, $director);

        $correction->fill([
            'status'               => Correction::RETURNED,
            'regional_reviewer_id' => $director->id,
            'regional_reviewed_at' => now(),
            'regional_note'        => $note,
        ])->addStep('returned', $director, $note)->save();

        $province = $correction->province;
        $label    = $correction->periodLabel();
        $this->notify($director, $this->provinceTeam($province, false), $correction,
            "Your correction request for {$label} was returned by the regional director: \"" . Str::limit($note, 120) . '"',
            "/provincial-kpi/{$correction->year}");
        ActivityLogHelper::kpiCorrection('returned', $province->name, $label, $note);
    }

    // $openUntil is a date: the period stays open through the end of that day.
    public function approve(Correction $correction, User $admin, string $openUntil, ?string $note): void
    {
        $this->expectStatus($correction, Correction::PENDING_CENTRAL);
        $until = Carbon::parse($openUntil)->endOfDay();
        if ($until->isPast()) {
            throw ValidationException::withMessages(['open_until' => 'Choose today or a later date.']);
        }

        $correction->fill([
            'status'              => Correction::APPROVED,
            'central_reviewer_id' => $admin->id,
            'central_reviewed_at' => now(),
            'central_note'        => $note,
            'open_until'          => $until,
        ])->addStep('approved', $admin, $note, ['open_until' => $until->toDateString()])->save();

        $province = $correction->province;
        $label    = $correction->periodLabel();
        $date     = $until->format('M j, Y');
        $this->notify($admin, $this->provinceTeam($province, true), $correction,
            "Correction approved: {$label} is open for changes until {$date}.",
            "/provincial-kpi/{$correction->year}");
        $this->notify($admin, $this->regionalDirectors($province), $correction,
            "The central office approved {$province->name}'s correction for {$label} (open until {$date}).",
            '/kpi-corrections');
        ActivityLogHelper::kpiCorrection('approved', $province->name, $label, "open until {$date}");
    }

    public function decline(Correction $correction, User $admin, string $note): void
    {
        $this->expectStatus($correction, Correction::PENDING_CENTRAL);

        $correction->fill([
            'status'              => Correction::DECLINED,
            'central_reviewer_id' => $admin->id,
            'central_reviewed_at' => now(),
            'central_note'        => $note,
        ])->addStep('declined', $admin, $note)->save();

        $province = $correction->province;
        $label    = $correction->periodLabel();
        $this->notify($admin, $this->provinceTeam($province, true), $correction,
            "Correction declined for {$label}: \"" . Str::limit($note, 120) . '"',
            "/provincial-kpi/{$correction->year}");
        $this->notify($admin, $this->regionalDirectors($province), $correction,
            "The central office declined {$province->name}'s correction for {$label}.",
            '/kpi-corrections');
        ActivityLogHelper::kpiCorrection('declined', $province->name, $label, $note);
    }

    // ── Who sees what ────────────────────────────────────────────────────────

    // The requests a user can see on the Correction Requests page: a regional
    // director sees their region's, the super admin sees every one.
    public function visibleTo(User $user): Collection
    {
        return Correction::with(['province.region', 'requester.profile', 'regionalReviewer.profile', 'centralReviewer.profile'])
            ->when($user->role === 'regional_director', fn($q) => $q->whereHas('province', fn($p) => $p->where('region_id', $user->region_id)))
            ->latest('updated_at')
            ->get();
    }

    // Requests waiting on this user: pending_regional in a regional director's
    // region, pending_central for the super admin.
    public function inboxCount(User $user): int
    {
        return match ($user->role) {
            'regional_director' => Correction::where('status', Correction::PENDING_REGIONAL)
                ->whereHas('province', fn($p) => $p->where('region_id', $user->region_id))->count(),
            'super_admin'       => Correction::where('status', Correction::PENDING_CENTRAL)->count(),
            default             => 0,
        };
    }

    // The shape every screen shows a request in.
    public static function present(Correction $c): array
    {
        $name = fn(?User $u) => ProvinceOverviewService::personName($u);

        return [
            'id'              => $c->id,
            'province'        => $c->province?->name,
            'province_url_id' => Crypt::encrypt($c->province_id),
            'region'          => $c->province?->region?->name,
            'year'            => $c->year,
            'quarter'         => $c->quarter,
            'period'          => $c->periodLabel(),
            'status'          => $c->status,
            'status_label'    => $c->statusLabel(),
            'reason'          => $c->reason,
            'requested_by'    => $name($c->requester),
            'requested_at'    => $c->created_at?->toIso8601String(),
            'regional'        => $c->regional_reviewed_at ? [
                'by'   => $name($c->regionalReviewer),
                'at'   => $c->regional_reviewed_at->toIso8601String(),
                'note' => $c->regional_note,
            ] : null,
            'central'         => $c->central_reviewed_at ? [
                'by'   => $name($c->centralReviewer),
                'at'   => $c->central_reviewed_at->toIso8601String(),
                'note' => $c->central_note,
            ] : null,
            // The chosen last day (open through its end), as a plain date so
            // every screen shows the day the super admin picked.
            'open_until'      => $c->open_until?->toDateString(),
            'is_open'         => $c->isOpen(),
            'applied_at'      => $c->applied_at?->toIso8601String(),
            'revised_at'      => $c->revised_at?->toIso8601String(),
            // Every step so far: [{ step, by, at, note, open_until? }]
            'history'         => collect($c->history ?? [])
                ->map(fn($h) => array_intersect_key($h, array_flip(['step', 'by', 'at', 'note', 'open_until'])))
                ->values()->all(),
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function expectStatus(Correction $correction, string $status): void
    {
        if ($correction->status !== $status) {
            throw ValidationException::withMessages([
                'status' => "This request can't take that step now - it is: {$correction->statusLabel()}.",
            ]);
        }
    }

    private function assertOwnRegion(Correction $correction, User $director): void
    {
        abort_unless((int) $correction->province?->region_id === (int) $director->region_id, 403,
            'This request is from a province outside your region.');
    }

    private function regionalDirectors(Province $province): Collection
    {
        return User::whereRole('regional_director')->where('region_id', $province->region_id)->get();
    }

    // The province's admins, and its director when they should hear the outcome.
    private function provinceTeam(Province $province, bool $withDirector): Collection
    {
        $roles = $withDirector ? ['provincial_admin', 'provincial_director'] : ['provincial_admin'];
        return User::whereRoleIn($roles)->whereProvince($province->id)->get();
    }

    // Bell + browser push (NotificationHelper::notify), each person once and
    // never the one taking the step. One tag per request, so a newer browser
    // notification about it replaces the older one.
    private function notify(User $sender, iterable $receivers, Correction $correction, string $message, string $url): void
    {
        $users = collect($receivers)->unique('id')->reject(fn(User $u) => $u->id === $sender->id)->values();
        if ($users->isEmpty()) return;

        NotificationHelper::notify(
            $users,
            "{$correction->province?->name} · {$correction->periodLabel()} correction",
            $message,
            $url,
            'correction',
            "correction-{$correction->id}",
            $sender,
        );
    }
}
