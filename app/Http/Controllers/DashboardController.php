<?php

namespace App\Http\Controllers;

use App\Models\KPICategory;
use App\Models\KpiCorrectionRequest;
use App\Models\KpiQuarterApproval;
use App\Models\Region;
use App\Models\User;
use App\Services\ProvinceOverviewService;
use App\Services\RankingRelease;
use App\Services\RankingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        switch (auth()->user()->role) {
            case 'super_admin':
            case 'sub_admin':             return self::national_dashboard();
            case 'regional_director':     return self::regional_director_dashboard();
            case 'provincial_admin':      return self::provincial_admin_dashboard();
            case 'provincial_director':   return self::director_dashboard();
            case 'employee':              return self::employee_dashboard();
        }
    }

    // Super Admin and Sub Admin share the national dashboard.
    private function national_dashboard()
    {
        return inertia('Admin/Dashboard/Main', self::build_dashboard_payload());
    }

    // A regional director gets the same dashboard, limited to the provinces in
    // their region. Only provincial offices are ranked: each province keeps the
    // official rank it earned in its tier nationally — the region's provinces
    // are never ranked among themselves.
    private function regional_director_dashboard()
    {
        $region = Region::with('provinces')->findOrFail(auth()->user()->region_id);

        return inertia('Admin/Dashboard/Main', [
            'scope' => ['region' => $region->name],
            ...self::build_dashboard_payload($region->provinces->pluck('id')->toArray()),
        ]);
    }

    // What a province row carries only once its year's rankings are released.
    private const RANKING_FIELDS = [
        'rank', 'bucket', 'tier_size', 'total', 'total_pct', 'adjective_label',
        'subtotals', 'subtotals_pct', 'kpi_scores', 'category_ranks',
    ];

    // Payload for the shared dashboard. Released years (see RankingRelease)
    // carry official rankings; years still waiting for release — including the
    // current year, from day one — carry only each province's submission
    // progress, so their partial scores aren't shown anywhere. $provinceIds
    // limits which provinces are returned without changing anyone's rank.
    private static function build_dashboard_payload(?array $provinceIds = null): array
    {
        $svc     = new RankingService();
        $release = app(RankingRelease::class);

        $released = $release->releasedYears();
        $pending  = $release->pendingYears();
        $current  = (int) now()->year;
        if (!in_array($current, $released, true) && !in_array($current, $pending, true) && !$release->isReleased($current)) {
            array_unshift($pending, $current);
        }

        // Entry KPIs are the ones a director fills in by hand (the delinquency %
        // is derived from two of them); the scored ones also need a target.
        $entryKpis   = DB::table('kpis')->whereNull('deleted_at')->whereNull('derivation_type')->get(['id', 'is_scored']);
        $submissions = self::count_submissions($provinceIds, $entryKpis);
        $approved    = KpiQuarterApproval::query()
            ->when($provinceIds !== null, fn($q) => $q->whereIn('province_id', $provinceIds))
            ->selectRaw('province_id, year, COUNT(DISTINCT quarter) as quarters')
            ->groupBy('province_id', 'year')->get()
            ->mapWithKeys(fn($r) => ["{$r->year}-{$r->province_id}" => (int) $r->quarters]);

        $build = function (int $year, bool $isReleased) use ($svc, $provinceIds, $submissions, $approved) {
            $tiers = $provinceIds === null ? $svc->rankByYear($year) : $svc->officialRankingsFor($year, $provinceIds);
            foreach ($tiers as &$rows) {
                foreach ($rows as &$row) {
                    $row['province_url_id']   = Crypt::encrypt($row['province_id']);
                    $row['submission']        = $submissions[$year][$row['province_id']] ?? ['accomplished' => 0, 'targets' => 0];
                    $row['approved_quarters'] = $approved["{$year}-{$row['province_id']}"] ?? 0;
                    if (!$isReleased) {
                        $row = array_diff_key($row, array_flip(self::RANKING_FIELDS));
                    }
                }
            }
            unset($rows, $row);
            return $tiers;
        };

        return [
            'available_years'     => $released,
            'pending_years'       => $pending,
            'release'             => collect([...$released, ...$pending])->mapWithKeys(fn($y) => [$y => $release->status($y)]),
            'revisions'           => self::revisions($released, $provinceIds),
            'kpi_categories'      => KPICategory::catalog(),
            'rankings_by_year'    => collect($released)->mapWithKeys(fn($y) => [$y => $build($y, true)]),
            'pending_by_year'     => collect($pending)->mapWithKeys(fn($y) => [$y => $build($y, false)]),
            'submission_expected' => [
                'accomplished' => $entryKpis->count(),
                'targets'      => $entryKpis->where('is_scored', true)->count(),
            ],
        ];
    }

    // Released years whose figures were changed afterwards through an approved
    // correction: { <year>: { at, provinces: [{ province_id, province, periods, at }] } }.
    // `at` is the latest revision nationally — any province's correction can
    // move ranks in its tier — while `provinces` lists only the ones in scope.
    private static function revisions(array $years, ?array $provinceIds): array
    {
        return KpiCorrectionRequest::with('province')
            ->whereNotNull('revised_at')->whereIn('year', $years)
            ->get()->groupBy('year')
            ->map(fn(Collection $rows) => [
                'at'        => $rows->sortByDesc('revised_at')->first()->revised_at->toIso8601String(),
                'provinces' => $rows
                    ->filter(fn($c) => $provinceIds === null || in_array((int) $c->province_id, array_map('intval', $provinceIds), true))
                    ->groupBy('province_id')
                    ->map(fn(Collection $cs) => [
                        'province_id' => (int) $cs->first()->province_id,
                        'province'    => $cs->first()->province?->name,
                        'periods'     => $cs->sortBy('quarter')->map->periodLabel()->unique()->values()->all(),
                        'at'          => $cs->sortByDesc('revised_at')->first()->revised_at->toIso8601String(),
                    ])->values()->all(),
            ])->all();
    }

    // Per year and province: how many entry KPIs have an accomplishment, and how
    // many scored ones have a usable (> 0) target. Values are parsed the same way
    // RankingService scores them, so "blank", "-" and "n/a" count as missing.
    private static function count_submissions(?array $provinceIds, Collection $entryKpis): array
    {
        $scoredIds = $entryKpis->where('is_scored', true)->pluck('id')->flip();

        $rows = DB::table('provincial_director_kpis as pk')
            ->whereNotNull('pk.province_id')
            ->when($provinceIds !== null, fn($q) => $q->whereIn('pk.province_id', $provinceIds))
            ->whereIn('pk.kpi_id', $entryKpis->pluck('id'))
            ->select('pk.province_id', 'pk.year', 'pk.kpi_id', 'pk.target', 'pk.accomplished')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->year][$r->province_id] ??= ['accomplished' => 0, 'targets' => 0];

            if (RankingService::parseNumber($r->accomplished) !== null) {
                $out[$r->year][$r->province_id]['accomplished']++;
            }
            $target = RankingService::parseNumber($r->target);
            if (isset($scoredIds[$r->kpi_id]) && $target !== null && $target > 0) {
                $out[$r->year][$r->province_id]['targets']++;
            }
        }
        return $out;
    }

    public function map_index()
    {
        return inertia('Admin/Map/Main', self::build_ranking_payload());
    }

    // Single source of truth for ranking data delivered to Inertia views.
    // Shape:
    //   available_years   : [2025, 2024, ...]
    //   kpi_categories    : [{id, code, name, weight, kpis: [{id, code, name, weight, ...}]}]
    //   rankings_by_year  : { <year>: { <province_category>: [ranked rows ...] } }
    // $provinceIds optionally restricts the ranking data to a subset of provinces
    // (e.g. all provinces within a Regional Admin's region).
    private static function build_ranking_payload(?array $provinceIds = null): array
    {
        $svc   = new RankingService();
        $years = app(RankingRelease::class)->releasedYears();

        $rankings = $svc->rankAllYears($provinceIds, $years);
        foreach ($rankings as &$tiers) {
            foreach ($tiers as &$records) {
                foreach ($records as &$record) {
                    $record['province_url_id'] = Crypt::encrypt($record['province_id']);
                }
            }
        }
        unset($tiers, $records, $record);

        return [
            'available_years'  => $years,
            'kpi_categories'   => KPICategory::catalog(),
            'rankings_by_year' => $rankings,
            'regions'          => Region::orderBy('name')->get(['id', 'name', 'island_under']),
        ];
    }

    // The provincial admin enters the province's figures and manages its
    // accounts, so their dashboard is a to-do list: what's due, how complete
    // it is, what needs fixing — plus a compact view of where they stand.
    private function provincial_admin_dashboard()
    {
        $province = auth()->user()->province ?? abort(403, 'Your account is not assigned to a province.');
        $svc      = app(ProvinceOverviewService::class);
        $director = User::whereRole('provincial_director')->whereProvince($province->id)->with('profile')->first();

        $reporting = $svc->reporting($province->id);

        return inertia('ProvincialAdmin/Dashboard/Main', [
            'province'       => ['name' => $province->name, 'category' => $province->category],
            'reporting'      => $reporting,
            'attention'      => $svc->attention($reporting, $director !== null),
            'records'        => $svc->records($province->id, $director?->id),
            'recent_entries' => $svc->recentEntries($province->id),
            'staff'          => $svc->staff($province->id, $director),
            'standing'       => $svc->performance($province->id)['standing'],
            'release'        => app(RankingRelease::class)->status($reporting['year']),
        ]);
    }

    // The provincial director approves the figures and answers for the
    // province's standing: where it ranks, what's waiting for them, and where
    // the points are being lost.
    private function director_dashboard()
    {
        $province  = auth()->user()->province ?? abort(403, 'Your account is not assigned to a province.');
        $svc       = app(ProvinceOverviewService::class);
        $reporting = $svc->reporting($province->id);

        return inertia('ProvincialDirector/Dashboard/Main', [
            'province'   => ['name' => $province->name, 'category' => $province->category],
            'categories' => collect(KPICategory::catalog())->map(fn($c) => [
                'code' => $c['code'], 'name' => $c['name'], 'weight' => $c['weight'],
            ])->values(),
            ...$svc->performance($province->id),
            'release'    => app(RankingRelease::class)->status($reporting['year']),
            'awaiting_approval' => [
                'year'     => $reporting['year'],
                'quarters' => collect($reporting['quarters'])
                    ->filter(fn($q) => $q['has_data'] && !$q['approved_by'])
                    ->map(fn($q) => ['quarter' => $q['quarter'], 'entered' => $q['entered']])
                    ->values(),
                'expected' => $reporting['expected']['accomplished'],
            ],
        ]);
    }

    private function employee_dashboard()
    {
        $user     = auth()->user();
        $province = $user->province;

        $provinceIds = null;
        $myRegion    = null;
        if ($province?->region_id) {
            $region      = Region::with('provinces')->find($province->region_id);
            $myRegion    = $region?->name;
            $provinceIds = $region?->provinces->pluck('id')->toArray();
        }

        return inertia('Employee/Dashboard/Main', [
            'my_province' => $province?->name,
            'my_region'   => $myRegion,
            ...self::build_ranking_payload($provinceIds),
        ]);
    }

    public function regional_map_index()
    {
        $user        = auth()->user();
        $region      = Region::with('provinces')->findOrFail($user->region_id);
        $provinceIds = $region->provinces->pluck('id')->toArray();

        return inertia('Admin/Map/Main', self::build_ranking_payload($provinceIds));
    }
}
