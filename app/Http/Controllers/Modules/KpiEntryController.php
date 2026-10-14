<?php

namespace App\Http\Controllers\Modules;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\KpiCorrectionRequest;
use App\Models\KpiQuarterApproval;
use App\Models\KpiQuarterValue;
use App\Models\KPICategory;
use App\Models\Province;
use App\Models\ProvincialDirectorKPI;
use App\Models\User;
use App\Services\KpiCorrectionService;
use App\Services\KpiEntryService;
use App\Services\RankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

// The quarterly KPI entry screen. The provincial admin enters their province's
// targets and Q1–Q4 accomplishments; the provincial director views the same
// screen and approves each quarter. Super admin and sub admin open any province
// from the KPI Data list. Locks, roll-ups and approvals live in KpiEntryService.
class KpiEntryController extends Controller
{
    public function __construct(private KpiEntryService $entries) {}

    // ── Provincial admin / director: their own province ───────────────────

    public function own(?int $year = null)
    {
        return $this->page($this->ownProvinceId(), $year, '/provincial-kpi');
    }

    public function saveOwn(Request $request, int $year)
    {
        return $this->save($request, $this->ownProvinceId(), $year);
    }

    public function approve(Request $request, int $year, int $quarter)
    {
        abort_unless($quarter >= 1 && $quarter <= 4, 404);
        $data       = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $provinceId = $this->ownProvinceId();

        KpiQuarterApproval::updateOrCreate(
            ['province_id' => $provinceId, 'year' => $year, 'quarter' => $quarter],
            ['approved_by' => $request->user()->id, 'note' => $data['note'] ?? null],
        );

        return back()->with('success', "Q{$quarter} {$year} approved.");
    }

    // ── Super admin / sub admin: any province ─────────────────────────────

    public function province(string $id, ?int $year = null)
    {
        $provinceId = (int) Crypt::decrypt($id);
        return $this->page($provinceId, $year, "/kpi-data/{$id}");
    }

    public function saveProvince(Request $request, string $id, int $year)
    {
        return $this->save($request, (int) Crypt::decrypt($id), $year);
    }

    // ── Shared ────────────────────────────────────────────────────────────

    private function page(int $provinceId, ?int $year, string $baseUrl)
    {
        $province = Province::findOrFail($provinceId);
        $user     = auth()->user();

        // Reporting years: every year with data, plus the current one so a new
        // year can be started.
        $years = collect((new RankingService())->availableYears())
            ->map(fn($y) => (int) $y)->push((int) now()->year)
            ->unique()->sortDesc()->values();
        $year ??= (int) now()->year;

        $director = User::whereRole('provincial_director')->whereProvince($provinceId)->with('profile')->first();

        $annual   = ProvincialDirectorKPI::where('province_id', $provinceId)->where('year', $year)->get()->keyBy('kpi_id');
        $quarters = KpiQuarterValue::where('province_id', $provinceId)->where('year', $year)->get()->groupBy('kpi_id');

        $recordModules = [
            'func_linkages_established' => ['label' => 'Linkages',       'path' => '/linkages'],
            'supp_facebook_posts'       => ['label' => 'Facebook posts', 'path' => '/facebook-posts'],
        ];

        $categories = KPICategory::catalog(function ($kpi) use ($annual, $quarters, $recordModules, $director, $year) {
            $row = $annual->get($kpi->id);
            $qs  = $quarters->get($kpi->id, collect())->keyBy('quarter');
            $module = $recordModules[$kpi->code] ?? null;

            return [
                // entry: typed in · record: counted from a record module · derived: calculated
                'entry_type'   => $kpi->derivation_type ? 'derived' : ($module ? 'record' : 'entry'),
                'module'       => $module && $director ? [
                    'label' => $module['label'],
                    'url'   => "{$module['path']}/{$director->id}/{$year}",
                ] : null,
                'target'       => RankingService::parseNumber($row?->target),
                'target_raw'   => $row?->target,
                'quarters'     => collect([1, 2, 3, 4])->mapWithKeys(fn($q) => [$q => $qs->get($q)?->accomplished])->all(),
                'annual'       => $row?->accomplished,
                // Entered as one annual figure, before quarterly reporting.
                'annual_only'  => $qs->isEmpty() && $row?->accomplished !== null,
            ];
        });

        $deadlines = $this->entries->deadlinesFor($year);
        $approvals = KpiQuarterApproval::with('approver.profile')
            ->where('province_id', $provinceId)->where('year', $year)->get()->keyBy('quarter');

        $periods = collect([1, 2, 3, 4])->map(function ($q) use ($provinceId, $year, $deadlines, $approvals) {
            $correction = $this->entries->openCorrection($provinceId, $year, $q);
            $approval   = $approvals->get($q);
            return [
                'quarter'       => $q,
                'deadline'      => ($deadlines[$q] ?? null)?->deadline?->toDateString(),
                'open'          => $this->entries->isQuarterOpen($provinceId, $year, $q),
                'reopened_until'=> $correction?->open_until?->toDateString(),
                'approval'      => $approval ? [
                    'by' => self::personName($approval->approver),
                    'at' => $approval->updated_at->toIso8601String(),
                    'note' => $approval->note,
                ] : null,
            ];
        })->values();

        $isCentral = in_array($user->role, ['super_admin', 'sub_admin'], true);

        // This year's correction requests, newest first (see KpiCorrectionService).
        $corrections = KpiCorrectionRequest::with(['province.region', 'requester.profile', 'regionalReviewer.profile', 'centralReviewer.profile'])
            ->where('province_id', $provinceId)->where('year', $year)
            ->latest('id')->get()
            ->map(fn($c) => KpiCorrectionService::present($c))->values();

        return inertia('Kpi/Entry', [
            'province'       => ['name' => $province->name, 'category' => $province->category],
            'director'       => $director ? ['id' => $director->id, 'name' => self::personName($director)] : null,
            'year'           => $year,
            'years'          => $years,
            'base_url'       => $baseUrl,
            'kpi_categories' => $categories,
            'periods'        => $periods,
            'targets_open'   => $this->entries->areTargetsOpen($provinceId, $year),
            'targets_reopened_until' => $this->entries->openCorrection($provinceId, $year, null)?->open_until?->toDateString(),
            'can_edit'       => $isCentral || $user->role === 'provincial_admin',
            'can_approve'    => $user->role === 'provincial_director',
            'is_central'     => $isCentral,
            'corrections'    => $corrections,
            // Only the province's own admin asks to reopen a locked period.
            'can_request'    => $user->role === 'provincial_admin',
        ]);
    }

    private function save(Request $request, int $provinceId, int $year)
    {
        $data = $request->validate([
            'targets'      => ['array'],
            'targets.*'    => ['nullable', 'numeric', 'min:0'],
            'quarters'     => ['array'],
            'quarters.*'   => ['array'],
            'quarters.*.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $quarters = $data['quarters'] ?? [];
        foreach (array_keys($quarters) as $q) {
            if (!in_array((int) $q, [1, 2, 3, 4], true)) {
                throw ValidationException::withMessages(['quarters' => 'Unknown quarter.']);
            }
        }

        $this->entries->saveYear($provinceId, $year, $data['targets'] ?? [], $quarters, $request->user());

        $province = Province::find($provinceId);
        ActivityLogHelper::updateKpiFigures($province->name, $year);

        return back()->with('success', "Saved {$province->name}'s {$year} KPI figures.");
    }

    private function ownProvinceId(): int
    {
        return auth()->user()->province_id ?? abort(403, 'Your account is not assigned to a province.');
    }

    private static function personName(?User $user): ?string
    {
        if (!$user) return null;
        $p = $user->profile;
        $name = trim(implode(' ', array_filter([$p?->prefix, $p?->first_name, $p?->last_name, $p?->suffix])));
        return $name ?: $user->username;
    }
}
