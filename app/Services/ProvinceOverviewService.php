<?php

namespace App\Services;

use App\Models\FacebookPost;
use App\Models\KPI;
use App\Models\KpiCorrectionRequest;
use App\Models\KpiQuarterApproval;
use App\Models\KpiQuarterValue;
use App\Models\Linkage;
use App\Models\ProvincialDirectorKPI;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// What a provincial office needs at a glance, for the provincial admin and
// provincial director dashboards:
//   performance — official standing, change since last year, the gap to the
//                 next rank and group, where points are lost, the tier, the trend
//   reporting   — this year's quarters: what's due, how complete, approvals
//   attention   — the reporting gaps the admin should act on
// Every rank here is the official one (national, within the tier).
class ProvinceOverviewService
{
    public function __construct(
        private RankingService $ranking,
        private KpiEntryService $entries,
        private RankingRelease $release,
    ) {}

    // ── Performance ──────────────────────────────────────────────────────────

    public function performance(int $provinceId): array
    {
        // Only released years: a year still being reported shows no standing.
        $years = $this->release->releasedYears(); // newest first

        // year => [the province's tier rows, the province's row]
        $byYear = [];
        foreach ($years as $year) {
            $byYear[$year] = self::tierAndRow($this->ranking->rankByYear($year), $provinceId);
        }

        $latest   = $years[0] ?? null;
        [$tier, $mine] = $latest ? $byYear[$latest] : [[], null];
        $previous = isset($years[1]) ? $byYear[$years[1]][1] : null;
        $ranked   = array_values(array_filter($tier, fn($r) => $r['status'] === 'ranked'));
        $isRanked = $mine && $mine['status'] === 'ranked';

        return [
            'standing'    => $mine ? [
                'year'              => $latest,
                'previous_year'     => $years[1] ?? null,
                'status'            => $mine['status'],
                'tier'              => $mine['category'],
                'rank'              => $mine['rank'],
                'tier_size'         => $mine['tier_size'],
                'bucket'            => $mine['bucket'],
                'total_pct'         => $mine['total_pct'],
                'adjective_label'   => $mine['adjective_label'],
                'change'            => self::change($mine, $previous),
                'gap_to_next_rank'  => $isRanked ? self::gapToNextRank($ranked, $mine) : null,
                'gap_to_next_group' => $isRanked ? self::gapToNextGroup($ranked, $mine) : null,
                'revised_at'        => $this->revisedAt($latest),
            ] : null,
            'improvement' => $isRanked ? $this->improvement($mine, $provinceId, $latest) : [],
            'versus_tier' => $isRanked ? self::versusTier($ranked, $mine) : null,
            'trend'       => collect(array_reverse($years))
                ->map(fn($y) => [$y, $byYear[$y][1]])
                ->filter(fn($pair) => $pair[1] && $pair[1]['status'] === 'ranked')
                ->map(fn($pair) => [
                    'year'      => $pair[0],
                    'total_pct' => $pair[1]['total_pct'],
                    'rank'      => $pair[1]['rank'],
                    'tier_size' => $pair[1]['tier_size'],
                ])->values()->all(),
        ];
    }

    // When a correction last changed a released year's figures — any
    // province's correction can move ranks in the tier. Null if never.
    private function revisedAt(int $year): ?string
    {
        $at = KpiCorrectionRequest::where('year', $year)->max('revised_at');
        return $at ? Carbon::parse($at)->toIso8601String() : null;
    }

    private static function tierAndRow(array $ranking, int $provinceId): array
    {
        foreach ($ranking as $tierRows) {
            foreach ($tierRows as $row) {
                if ((int) $row['province_id'] === $provinceId) return [$tierRows, $row];
            }
        }
        return [[], null];
    }

    // Points and places since the previous year; null unless ranked in both.
    private static function change(array $mine, ?array $previous): ?array
    {
        if (!$previous || $mine['status'] !== 'ranked' || $previous['status'] !== 'ranked') return null;

        return [
            'points' => round($mine['total_pct'] - $previous['total_pct'], 2),
            'places' => $previous['rank'] - $mine['rank'],
        ];
    }

    // Score needed to draw level with the province one rank higher.
    private static function gapToNextRank(array $ranked, array $mine): ?array
    {
        if ($mine['rank'] <= 1) return null;
        $above = collect($ranked)->firstWhere('rank', $mine['rank'] - 1);

        return ['rank' => $mine['rank'] - 1, 'points' => round(max(0, $above['total_pct'] - $mine['total_pct']), 2)];
    }

    // Score needed to reach the lowest-scoring province of the next group up.
    private static function gapToNextGroup(array $ranked, array $mine): ?array
    {
        $next = ['Low' => 'Average', 'Average' => 'Top'][$mine['bucket'] ?? ''] ?? null;
        if (!$next) return null;

        $threshold = collect($ranked)->where('bucket', $next)->min('total_pct');
        return ['group' => $next, 'points' => round(max(0, $threshold - $mine['total_pct']), 2)];
    }

    // The scored KPIs costing the most: each KPI is worth weight × 100 points of
    // the total; "missed" is what the province didn't earn of that. Biggest first.
    private function improvement(array $mine, int $provinceId, int $year, int $limit = 5): array
    {
        $kpis   = KPI::withTrashed()->whereIn('id', array_column($mine['kpi_scores'], 'kpi_id'))->get()->keyBy('id');
        $values = ProvincialDirectorKPI::where('province_id', $provinceId)->where('year', $year)->get()->keyBy('kpi_id');

        return collect($mine['kpi_scores'])
            ->map(function ($score) use ($kpis, $values) {
                $possible = (float) $kpis[$score['kpi_id']]->weight * 100;
                $earned   = $score['weighted_score'] * 100;
                return [
                    'kpi_id'       => $score['kpi_id'],
                    'name'         => $kpis[$score['kpi_id']]->name,
                    'category'     => $score['category_code'],
                    'rating'       => $score['adjective_label'],
                    'actual_pct'   => $score['actual_pct'],
                    'target'       => $values->get($score['kpi_id'])?->target,
                    'accomplished' => $values->get($score['kpi_id'])?->accomplished,
                    'earned'       => round($earned, 2),
                    'possible'     => round($possible, 2),
                    'missed'       => round($possible - $earned, 2),
                ];
            })
            ->filter(fn($k) => $k['missed'] > 0)
            ->sortByDesc('missed')
            ->take($limit)->values()->all();
    }

    // The province beside its tier's average and best, without naming anyone.
    private static function versusTier(array $ranked, array $mine): array
    {
        $stats = fn(callable $value) => [
            'tier_average' => round(collect($ranked)->avg($value), 2),
            'tier_best'    => round(collect($ranked)->max($value), 2),
        ];

        return [
            'tier'       => $mine['category'],
            'size'       => count($ranked),
            'total'      => ['mine' => $mine['total_pct'], ...$stats(fn($r) => $r['total_pct'])],
            'categories' => collect($mine['subtotals_pct'])->map(fn($pct, $code) => [
                'code' => $code,
                'mine' => $pct,
                ...$stats(fn($r) => $r['subtotals_pct'][$code] ?? 0),
            ])->values()->all(),
        ];
    }

    // ── Reporting ────────────────────────────────────────────────────────────

    // This year's reporting: the quarter due next (the open one with the nearest
    // deadline ahead; the calendar quarter when no deadlines are set), each
    // quarter's completeness and approval, and how many targets are set.
    public function reporting(int $provinceId): array
    {
        $year       = (int) now()->year;
        $deadlines  = $this->entries->deadlinesFor($year);
        $typed      = $this->entries->entryKpis();
        $targetKpis = KPI::whereNull('derivation_type')->where('is_scored', true)->pluck('id');

        $values = KpiQuarterValue::where('province_id', $provinceId)->where('year', $year)
            ->whereNotNull('accomplished')->get()->groupBy('quarter');
        $approvals = KpiQuarterApproval::with('approver.profile')
            ->where('province_id', $provinceId)->where('year', $year)->get()->keyBy('quarter');

        $quarters = collect([1, 2, 3, 4])->map(function ($q) use ($provinceId, $year, $deadlines, $typed, $values, $approvals) {
            $rows     = $values->get($q, collect());
            $approval = $approvals->get($q);
            return [
                'quarter'     => $q,
                'entered'     => $rows->whereIn('kpi_id', $typed->keys())->pluck('kpi_id')->unique()->count(),
                'has_data'    => $rows->isNotEmpty(),
                'deadline'    => ($deadlines[$q] ?? null)?->deadline->toDateString(),
                'open'        => $this->entries->isQuarterOpen($provinceId, $year, $q),
                'approved_by' => $approval ? self::personName($approval->approver) : null,
                'approved_at' => $approval?->updated_at->toIso8601String(),
            ];
        })->all();

        $targetsSet = ProvincialDirectorKPI::where('province_id', $provinceId)->where('year', $year)
            ->whereIn('kpi_id', $targetKpis)->get()
            ->filter(fn($r) => ($t = RankingService::parseNumber($r->target)) !== null && $t > 0)
            ->count();

        $upcoming = collect($deadlines)->reject(fn($d) => $d->hasPassed())->sortBy('deadline')->first();
        $focus    = $upcoming?->quarter ?? (int) ceil(now()->month / 3);
        $due      = $deadlines[$focus] ?? null;

        return [
            'year'           => $year,
            'focus_quarter'  => $focus,
            'focus_deadline' => $due?->deadline->toDateString(),
            'days_left'      => $due ? (int) now()->startOfDay()->diffInDays($due->deadline, false) : null,
            'expected'       => ['accomplished' => $typed->count(), 'targets' => $targetKpis->count()],
            'targets_set'    => $targetsSet,
            'targets_open'   => $this->entries->areTargetsOpen($provinceId, $year),
            'quarters'       => $quarters,
        ];
    }

    // The reporting gaps worth acting on, most urgent first. Each item links to
    // where it's fixed.
    public function attention(array $reporting, bool $hasDirector): array
    {
        $year     = $reporting['year'];
        $entryUrl = "/provincial-kpi/{$year}";
        $expected = $reporting['expected'];
        $quarters = collect($reporting['quarters']);
        $items    = [];

        if (!$hasDirector) {
            $items[] = ['level' => 'error', 'url' => '/users',
                'text' => 'No provincial director is assigned, so quarters can\'t be approved.'];
        }

        foreach ($quarters as $q) {
            $blank = $expected['accomplished'] - $q['entered'];
            if ($q['deadline'] && !$q['open'] && $blank > 0) {
                $items[] = ['level' => 'error', 'url' => $entryUrl,
                    'text' => "Q{$q['quarter']} closed with {$blank} " . ($blank === 1 ? 'KPI' : 'KPIs') . ' left blank.'];
            }
        }

        $focus = $quarters->firstWhere('quarter', $reporting['focus_quarter']);
        $blank = $expected['accomplished'] - $focus['entered'];
        if ($focus['open'] && $blank > 0) {
            $items[] = ['level' => 'warning', 'url' => $entryUrl,
                'text' => "{$blank} " . ($blank === 1 ? 'KPI still needs' : 'KPIs still need') . " a Q{$focus['quarter']} figure."];
        }

        $missingTargets = $expected['targets'] - $reporting['targets_set'];
        if ($reporting['targets_open'] && $missingTargets > 0) {
            $items[] = ['level' => 'warning', 'url' => $entryUrl,
                'text' => "{$missingTargets} of {$expected['targets']} {$year} targets aren't set yet."];
        }

        if ($hasDirector) {
            foreach ($quarters as $q) {
                if ($q['has_data'] && !$q['approved_by']) {
                    $items[] = ['level' => 'info', 'url' => $entryUrl,
                        'text' => "Q{$q['quarter']} is waiting for the director's approval."];
                }
            }
        }

        return $items;
    }

    // ── Other ────────────────────────────────────────────────────────────────

    // The latest quarterly figures entered, newest first.
    public function recentEntries(int $provinceId, int $limit = 6): array
    {
        return KpiQuarterValue::with(['kpi' => fn($q) => $q->withTrashed(), 'editor.profile'])
            ->where('province_id', $provinceId)
            ->latest('updated_at')->latest('id')
            ->take($limit)->get()
            ->map(fn($v) => [
                'kpi'          => $v->kpi?->name,
                'year'         => $v->year,
                'quarter'      => $v->quarter,
                'accomplished' => $v->accomplished,
                'by'           => self::personName($v->editor) ?? 'System',
                'at'           => $v->updated_at?->toIso8601String(),
            ])->all();
    }

    // This year's evidence records, with links to manage them.
    public function records(int $provinceId, ?int $directorId): array
    {
        $year = (int) now()->year;
        return [
            'year'           => $year,
            'linkages'       => Linkage::where('province_id', $provinceId)->where('year', $year)->count(),
            'facebook_posts' => FacebookPost::where('province_id', $provinceId)->where('year', $year)->count(),
            'linkages_url'   => $directorId ? "/linkages/{$directorId}/{$year}" : null,
            'posts_url'      => $directorId ? "/facebook-posts/{$directorId}/{$year}" : null,
        ];
    }

    public function staff(int $provinceId, ?User $director): array
    {
        $accounts = User::whereProvince($provinceId);

        return [
            'accounts'    => (clone $accounts)->count(),
            // Every account also holds the employee role, so staff means users
            // with no admin-tier role in effect.
            'staff'       => (clone $accounts)
                ->whereDoesntHave('roles', fn($q) => $q->whereIn('roles.name', Role::ADMIN_ROLES)->where('user_roles.locked', true))
                ->count(),
            'deactivated' => (clone $accounts)->where('activate', false)->count(),
            'director'    => self::personName($director),
        ];
    }

    public static function personName(?User $user): ?string
    {
        if (!$user) return null;
        $p    = $user->profile;
        $name = trim(implode(' ', array_filter([$p?->prefix, $p?->first_name, $p?->last_name, $p?->suffix])));
        return $name ?: $user->username;
    }
}
