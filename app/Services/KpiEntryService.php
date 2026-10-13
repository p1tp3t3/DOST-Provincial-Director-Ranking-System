<?php

namespace App\Services;

use App\Models\KPI;
use App\Models\KpiCorrectionRequest;
use App\Models\KpiQuarterApproval;
use App\Models\KpiQuarterValue;
use App\Models\KpiReportingDeadline;
use App\Models\ProvincialDirectorKPI;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Entering a province's KPI figures: which periods are still open, saving
// quarterly accomplishments and annual targets, and rolling the quarters up
// into the annual figure the ranking reads (provincial_director_kpis).
//
// A quarter is open until its deadline (set by the super admin) has passed;
// after that only an approved correction request reopens it. A year's targets
// close with its Q4 deadline. No deadline set means the period is open.
class KpiEntryService
{
    // How long an approved correction keeps a period open.
    public const CORRECTION_WINDOW_DAYS = 7;

    // KPIs whose accomplishment comes from a record module, not from typing:
    // code => [records table, date column]. Their quarterly figures are counted
    // from the records' dates.
    public const RECORD_KPIS = [
        'func_linkages_established' => ['linkages', 'date_signed'],
        'supp_facebook_posts'       => ['facebook_posts', 'date_posted'],
    ];

    // ── Open / locked ────────────────────────────────────────────────────────

    public function deadlinesFor(int $year): array
    {
        return KpiReportingDeadline::where('year', $year)->get()->keyBy('quarter')->all();
    }

    public function isQuarterOpen(int $provinceId, int $year, int $quarter): bool
    {
        $deadline = KpiReportingDeadline::where('year', $year)->where('quarter', $quarter)->first();
        if (!$deadline || !$deadline->hasPassed()) return true;

        return $this->openCorrection($provinceId, $year, $quarter) !== null;
    }

    public function areTargetsOpen(int $provinceId, int $year): bool
    {
        $q4 = KpiReportingDeadline::where('year', $year)->where('quarter', 4)->first();
        if (!$q4 || !$q4->hasPassed()) return true;

        return $this->openCorrection($provinceId, $year, null) !== null;
    }

    // An approved correction still inside its window. $quarter null = targets.
    public function openCorrection(int $provinceId, int $year, ?int $quarter): ?KpiCorrectionRequest
    {
        return KpiCorrectionRequest::where('province_id', $provinceId)
            ->where('year', $year)
            ->when($quarter === null, fn($q) => $q->whereNull('quarter'), fn($q) => $q->where('quarter', $quarter))
            ->where('status', KpiCorrectionRequest::APPROVED)
            ->where('open_until', '>', now())
            ->latest('open_until')
            ->first();
    }

    // ── Saving ───────────────────────────────────────────────────────────────

    // KPIs typed in by hand: not derived (the delinquency %) and not counted
    // from a record module.
    public function entryKpis()
    {
        return KPI::whereNull('derivation_type')
            ->whereNotIn('code', array_keys(self::RECORD_KPIS))
            ->get()->keyBy('id');
    }

    // Saves a year's figures for a province:
    //   $targets  — kpi_id => number|null, the year's targets
    //   $quarters — quarter => [kpi_id => number|null], accomplishments
    // Only the periods passed in are touched, and each must be open. A quarter
    // whose figures change loses its director approval.
    public function saveYear(int $provinceId, int $year, array $targets, array $quarters, User $by): void
    {
        if ($targets && !$this->areTargetsOpen($provinceId, $year)) {
            throw ValidationException::withMessages(['targets' => "The {$year} targets are locked. Request a correction to change them."]);
        }
        foreach (array_keys($quarters) as $quarter) {
            if (!$this->isQuarterOpen($provinceId, $year, (int) $quarter)) {
                throw ValidationException::withMessages(['quarters' => "Q{$quarter} {$year} is locked. Request a correction to change it."]);
            }
        }

        $entryKpis  = $this->entryKpis();
        // Record-backed KPIs still take a typed target; only derived ones don't.
        $targetKpis = KPI::whereNull('derivation_type')->pluck('id')->flip();
        $directorId = $this->currentDirectorId($provinceId);
        $touched    = [];

        DB::transaction(function () use ($provinceId, $year, $targets, $quarters, $by, $entryKpis, $targetKpis, $directorId, &$touched) {
            foreach ($targets as $kpiId => $value) {
                if (!$targetKpis->has($kpiId)) continue;
                $this->saveTarget($provinceId, (int) $kpiId, $year, $value, $directorId, $by->id);
                $touched[$kpiId] = true;
            }

            foreach ($quarters as $quarter => $values) {
                $changed = false;
                foreach ($values as $kpiId => $value) {
                    if (!$entryKpis->has($kpiId)) continue;
                    $changed = $this->saveQuarterValue($provinceId, (int) $kpiId, $year, (int) $quarter, $value, $directorId, $by->id) || $changed;
                    $touched[$kpiId] = true;
                }
                if ($changed) {
                    KpiQuarterApproval::where('province_id', $provinceId)->where('year', $year)->where('quarter', $quarter)->delete();
                }
            }

            foreach (array_keys($touched) as $kpiId) {
                $this->rollUp($provinceId, (int) $kpiId, $year, $directorId, $by->id);
            }
        });
    }

    private function saveTarget(int $provinceId, int $kpiId, int $year, $value, ?int $directorId, int $userId): void
    {
        $row = ProvincialDirectorKPI::firstOrNew(['province_id' => $provinceId, 'kpi_id' => $kpiId, 'year' => $year]);
        $new = self::toNumberString($value);
        if ($row->exists && RankingService::parseNumber($row->target) === RankingService::parseNumber($new)) return;

        $row->target                 = $new;
        $row->provincial_director_id = $directorId ?? $row->provincial_director_id;
        $row->updated_by             = $userId;
        $row->save();
    }

    // Returns whether the stored value changed. A cleared value is kept as a
    // null row so the KPI stays in quarterly mode (see rollUp).
    private function saveQuarterValue(int $provinceId, int $kpiId, int $year, int $quarter, $value, ?int $directorId, int $userId): bool
    {
        $new = ($value === null || $value === '') ? null : (float) $value;
        $row = KpiQuarterValue::firstOrNew([
            'province_id' => $provinceId, 'kpi_id' => $kpiId, 'year' => $year, 'quarter' => $quarter,
        ]);
        if ($row->exists && $row->accomplished === $new) return false;
        if (!$row->exists && $new === null) return false;

        $row->accomplished           = $new;
        $row->provincial_director_id = $directorId;
        $row->updated_by             = $userId;
        $row->save();
        return true;
    }

    // Recounts a record-backed KPI (linkages, Facebook posts) per quarter from
    // the records' dates, then rolls it up. Called whenever a record changes.
    public function syncRecordKpi(string $code, int $provinceId, int $year, ?int $userId): void
    {
        [$table, $dateColumn] = self::RECORD_KPIS[$code];
        $kpi = KPI::where('code', $code)->first();
        if (!$kpi) return;

        $dates = DB::table($table)->where('province_id', $provinceId)->where('year', $year)->pluck($dateColumn);
        $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        foreach ($dates as $date) {
            $counts[self::quarterOf($date)]++;
        }
        $any        = array_sum($counts) > 0;
        $directorId = $this->currentDirectorId($provinceId);

        foreach ($counts as $quarter => $count) {
            KpiQuarterValue::updateOrCreate(
                ['province_id' => $provinceId, 'kpi_id' => $kpi->id, 'year' => $year, 'quarter' => $quarter],
                ['accomplished' => $any ? $count : null, 'provincial_director_id' => $directorId, 'updated_by' => $userId],
            );
        }
        $this->rollUp($provinceId, $kpi->id, $year, $directorId, $userId);
    }

    // ── Rolling up ───────────────────────────────────────────────────────────

    // Writes the annual accomplishment from the quarters, using the KPI's
    // rollup_rule. A KPI with no quarter rows for the year was reported
    // annually (years before quarterly reporting), so its figure is left alone.
    public function rollUp(int $provinceId, int $kpiId, int $year, ?int $directorId, ?int $userId): void
    {
        $quarters = KpiQuarterValue::where('province_id', $provinceId)
            ->where('kpi_id', $kpiId)->where('year', $year)
            ->orderBy('quarter')->get();
        if ($quarters->isEmpty()) return;

        $rule   = KPI::withTrashed()->whereKey($kpiId)->value('rollup_rule') ?? 'sum';
        $annual = self::combine($quarters->pluck('accomplished', 'quarter')->all(), $rule);

        $row = ProvincialDirectorKPI::firstOrNew(['province_id' => $provinceId, 'kpi_id' => $kpiId, 'year' => $year]);
        $row->accomplished = $annual === null ? null : self::toNumberString($annual);

        if (!$row->exists && $row->accomplished === null) return;
        if ($row->exists && $row->accomplished === null && ($row->target === null || $row->target === '')) {
            $row->delete();
            return;
        }

        $row->provincial_director_id = $directorId ?? $row->provincial_director_id;
        $row->updated_by             = $userId ?? $row->updated_by;
        $row->save();
    }

    // After a KPI's rollup_rule changes: re-roll its annual figure for every
    // province and year that reports it quarterly.
    public function rollUpEverywhere(int $kpiId, ?int $userId): void
    {
        $periods = KpiQuarterValue::where('kpi_id', $kpiId)
            ->select('province_id', 'year')->distinct()->get();

        foreach ($periods as $p) {
            $this->rollUp($p->province_id, $kpiId, $p->year, null, $userId);
        }
    }

    // quarter => value|null, combined per rule over the quarters that have a value.
    public static function combine(array $values, string $rule): ?float
    {
        $present = array_filter($values, fn($v) => $v !== null);
        if (!$present) return null;

        return match ($rule) {
            'latest'  => (float) $present[max(array_keys($present))],
            'average' => array_sum($present) / count($present),
            default   => (float) array_sum($present),
        };
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function currentDirectorId(int $provinceId): ?int
    {
        return User::whereRole('provincial_director')->whereProvince($provinceId)->value('id');
    }

    // Quarter (1–4) a date falls in.
    public static function quarterOf($date): int
    {
        return (int) ceil(((int) date('n', strtotime((string) $date))) / 3);
    }

    // Record modules (linkages, Facebook posts) change a quarter's count, so a
    // record dated in a locked quarter can't be added, moved or removed.
    public function assertRecordQuarterOpen(int $provinceId, int $year, $date): void
    {
        $quarter = self::quarterOf($date);
        if (!$this->isQuarterOpen($provinceId, $year, $quarter)) {
            throw ValidationException::withMessages(['date' => "Q{$quarter} {$year} is locked. Request a correction to change its records."]);
        }
    }

    // 12.5 → "12.5", 3.0 → "3", null/'' → null.
    public static function toNumberString($value): ?string
    {
        if ($value === null || $value === '') return null;
        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    }
}
