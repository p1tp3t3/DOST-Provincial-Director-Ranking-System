<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\KPICategory;
use App\Models\Province;
use App\Models\Region;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        switch (auth()->user()->role) {
            case 'super_admin':           return self::super_admin_dashboard();
            case 'sub_admin':             return self::sub_admin_dashboard();
            case 'regional_admin':        return self::regional_admin_dashboard();
            case 'provincial_admin':      return self::provincial_admin_dashboard();
            case 'provincial_director':   return self::director_dashboard();
            case 'employee':              return self::employee_dashboard();
        }
    }

    private function super_admin_dashboard()
    {
        $payload = self::build_ranking_payload();

        // Provinces that reported any KPI data for the most recent year — lets the
        // central office spot data-collection gaps at a glance.
        $latestYear  = $payload['available_years'][0] ?? null;
        $activeProvinces = $latestYear
            ? DB::table('provincial_director_kpis as pk')
                ->join('users as u', 'u.id', '=', 'pk.provincial_director_id')
                ->join('user_province as up', 'up.user_id', '=', 'u.id')
                ->where('pk.year', $latestYear)
                ->distinct()->count('up.province_id')
            : 0;

        return inertia('Admin/Dashboard/Main', [
            'total_provinces'             => Province::count(),
            'total_users'                 => User::count(),
            'total_directors'             => User::where('role', 'provincial_director')->count(),
            'total_employees'             => User::where('role', 'employee')->count(),
            'active_reporting_provinces'  => $activeProvinces,
            ...$payload,
        ]);
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
        $svc = new RankingService();

        $categories = KPICategory::with(['kpis' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')->get()
            ->map(fn($cat) => [
                'id'     => $cat->id,
                'code'   => $cat->code,
                'name'   => $cat->name,
                'weight' => (float) $cat->weight,
                'kpis'   => $cat->kpis->map(fn($k) => [
                    'id'              => $k->id,
                    'code'            => $k->code,
                    'name'            => $k->name,
                    'weight'          => (float) $k->weight,
                    'is_scored'       => (bool) $k->is_scored,
                    'inverse_scoring' => (bool) $k->inverse_scoring,
                ])->values()->toArray(),
            ])->values()->toArray();

        $rankings = $svc->rankAllYears($provinceIds);
        foreach ($rankings as &$tiers) {
            foreach ($tiers as &$records) {
                foreach ($records as &$record) {
                    $record['province_url_id'] = Crypt::encrypt($record['province_id']);
                }
            }
        }
        unset($tiers, $records, $record);

        return [
            'available_years'  => $svc->availableYears(),
            'kpi_categories'   => $categories,
            'rankings_by_year' => $rankings,
            'regions'          => Region::orderBy('name')->get(['id', 'name', 'island_under']),
        ];
    }

    private function sub_admin_dashboard()
    {
        $payload = self::build_ranking_payload();

        $latestYear      = $payload['available_years'][0] ?? null;
        $activeProvinces = $latestYear
            ? DB::table('provincial_director_kpis as pk')
                ->join('users as u', 'u.id', '=', 'pk.provincial_director_id')
                ->join('user_province as up', 'up.user_id', '=', 'u.id')
                ->where('pk.year', $latestYear)
                ->distinct()->count('up.province_id')
            : 0;

        return inertia('Admin/Dashboard/Main', [
            'total_provinces'            => Province::count(),
            'total_users'                => User::count(),
            'total_directors'            => User::where('role', 'provincial_director')->count(),
            'total_employees'            => User::where('role', 'employee')->count(),
            'active_reporting_provinces' => $activeProvinces,
            ...$payload,
        ]);
    }

    private function provincial_admin_dashboard()
    {
        $payload = self::build_province_payload(auth()->user()->province_id);

        return inertia('ProvincialAdmin/Dashboard/Main', $payload);
    }

    // Shared province-level dashboard payload used by provincial_admin.
    // Returns employees, director, ranking, and recent logs for the given province.
    private static function build_province_payload(int $provinceId): array
    {
        $employees = User::where('role', 'employee')
            ->whereProvince($provinceId)
            ->with('profile')
            ->get()
            ->map(fn($e) => [
                'id'               => $e->id,
                'dost_employee_id' => $e->dost_employee_id,
                'name'             => trim(implode(' ', array_filter([
                    $e->profile?->first_name,
                    $e->profile?->middle_name ? $e->profile->middle_name[0] . '.' : null,
                    $e->profile?->last_name,
                ]))),
                'position'        => $e->profile?->position ?? '—',
                'profile_picture' => $e->profile?->profile_picture,
            ]);

        $director = User::where('role', 'provincial_director')
            ->whereProvince($provinceId)
            ->with('profile')
            ->first();

        $svc             = new RankingService();
        $availableYears  = $svc->availableYears();
        $latestYear      = $availableYears[0] ?? null;
        $provinceRanking = null;

        if ($latestYear) {
            $yearData = $svc->rankByYear($latestYear);
            foreach ($yearData as $rows) {
                foreach ($rows as $row) {
                    if (isset($row['province_id']) && $row['province_id'] === $provinceId) {
                        $provinceRanking = $row;
                        break 2;
                    }
                }
            }
        }

        $recentLogs = ActivityLog::with('user.profile')
            ->whereHas('user', fn($q) => $q->whereProvince($provinceId))
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(fn($log) => [
                'id'          => $log->id,
                'name'        => trim(implode(' ', array_filter([
                    $log->user?->profile?->first_name,
                    $log->user?->profile?->last_name,
                ]))),
                'role'        => $log->user?->role,
                'type'        => $log->type,
                'description' => $log->description,
                'created_at'  => $log->created_at,
            ]);

        return [
            'total_employees'  => $employees->count(),
            'employees'        => $employees,
            'director'         => $director ? [
                'id'               => $director->id,
                'dost_employee_id' => $director->dost_employee_id,
                'name'             => trim(implode(' ', array_filter([
                    $director->profile?->prefix,
                    $director->profile?->first_name,
                    $director->profile?->middle_name,
                    $director->profile?->last_name,
                    $director->profile?->suffix,
                ]))),
                'profile_picture'  => $director->profile?->profile_picture,
            ] : null,
            'province_ranking' => $provinceRanking,
            'latest_year'      => $latestYear,
            'recent_logs'      => $recentLogs,
        ];
    }

    private function director_dashboard()
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

        $payload = self::build_province_payload($user->province_id);
        unset($payload['recent_logs']);

        return inertia('ProvincialDirector/Dasbboard/Main', [
            ...$payload,
            'my_region'   => $myRegion,
            ...self::build_ranking_payload($provinceIds),
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

    private function regional_admin_dashboard()
    {
        $user        = auth()->user();
        $region      = Region::with('provinces')->findOrFail($user->region_id);
        $provinceIds = $region->provinces->pluck('id')->toArray();

        $latestYear      = (new RankingService())->availableYears()[0] ?? null;
        $activeProvinces = $latestYear
            ? DB::table('provincial_director_kpis as pk')
                ->join('users as u', 'u.id', '=', 'pk.provincial_director_id')
                ->join('user_province as up', 'up.user_id', '=', 'u.id')
                ->where('pk.year', $latestYear)
                ->whereIn('up.province_id', $provinceIds)
                ->distinct()->count('up.province_id')
            : 0;

        return inertia('RegionalAdmin/Dashboard/Main', [
            'region_name'                => $region->name,
            'region_id'                  => $region->id,
            'total_provinces'            => count($provinceIds),
            'total_users'                => User::whereProvinceIn($provinceIds)->count(),
            'total_directors'            => User::where('role', 'provincial_director')->whereProvinceIn($provinceIds)->count(),
            'total_employees'            => User::where('role', 'employee')->whereProvinceIn($provinceIds)->count(),
            'active_reporting_provinces' => $activeProvinces,
            ...self::build_ranking_payload(),
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
