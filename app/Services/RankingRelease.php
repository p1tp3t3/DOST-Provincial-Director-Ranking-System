<?php

namespace App\Services;

use App\Models\KpiQuarterApproval;
use App\Models\KpiReportingDeadline;
use App\Models\Province;

// When a year's rankings may be shown. A year is released once:
//   all_submitted   — every province has submitted it (its director approved
//                     all four quarters), or
//   deadline_passed — its Q4 reporting deadline has passed, whoever is missing, or
//   year_ended      — no Q4 deadline was set and the year is over (this keeps
//                     the years reported before quarterly reporting visible).
// Until then the year's figures can be entered and tracked, but no ranking,
// score or group is shown for it anywhere.
class RankingRelease
{
    private array $cache = [];

    public function __construct(private RankingService $ranking) {}

    // { year, released, reason, submitted, total, deadline }
    public function status(int $year): array
    {
        return $this->cache[$year] ??= $this->compute($year);
    }

    public function isReleased(int $year): bool
    {
        return $this->status($year)['released'];
    }

    // Years with data whose rankings are released, newest first.
    public function releasedYears(): array
    {
        return array_values(array_filter(
            array_map('intval', $this->ranking->availableYears()),
            fn($year) => $this->isReleased($year),
        ));
    }

    // Years with data still waiting for release, newest first.
    public function pendingYears(): array
    {
        return array_values(array_filter(
            array_map('intval', $this->ranking->availableYears()),
            fn($year) => !$this->isReleased($year),
        ));
    }

    private function compute(int $year): array
    {
        $total     = Province::count();
        $submitted = KpiQuarterApproval::where('year', $year)
            ->select('province_id')->groupBy('province_id')
            ->havingRaw('COUNT(DISTINCT quarter) = 4')
            ->get()->count();
        $q4 = KpiReportingDeadline::where('year', $year)->where('quarter', 4)->first();

        $reason = match (true) {
            $total > 0 && $submitted >= $total => 'all_submitted',
            $q4 !== null && $q4->hasPassed()    => 'deadline_passed',
            $q4 === null && $year < now()->year => 'year_ended',
            default                             => null,
        };

        return [
            'year'      => $year,
            'released'  => $reason !== null,
            'reason'    => $reason,
            'submitted' => $submitted,
            'total'     => $total,
            'deadline'  => $q4?->deadline->toDateString(),
        ];
    }
}
