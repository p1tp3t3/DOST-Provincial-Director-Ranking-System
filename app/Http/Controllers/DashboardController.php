<?php

namespace App\Http\Controllers;

use App\Models\KPI;
use App\Models\KPICategory;
use App\Models\Province;
use App\Models\Region;
use App\Models\User;
use App\Services\RankingService;
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
            case 'provincial_sub_admin':  return self::provincial_sub_admin_dashboard();
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

        return [
            'available_years'  => $svc->availableYears(),
            'kpi_categories'   => $categories,
            'rankings_by_year' => $svc->rankAllYears($provinceIds),
            'regions'          => Region::orderBy('name')->get(['id', 'name', 'island_under']),
        ];
    }

    private function sub_admin_dashboard()
    {
        return inertia('SubAdmin/Dashboard/Main', [
            'total_directors' => User::where('role', 'provincial_director')->count(),
            'total_employees' => User::where('role', 'employee')->count(),
            'total_provinces' => Province::count(),
        ]);
    }

    private function provincial_admin_dashboard()
    {
        return inertia('ProvincialAdmin/Dashboard/Main', [
            'total_employees' => User::where('role', 'employee')
                ->whereProvince(auth()->user()->province_id)->count(),
        ]);
    }

    private function provincial_sub_admin_dashboard()
    {
        return inertia('ProvincialSubAdmin/Dashboard/Main', [
            'total_employees' => User::where('role', 'employee')
                ->whereProvince(auth()->user()->province_id)->count(),
        ]);
    }

    private function director_dashboard()
    {
        return inertia('ProvincialDirector/Dasbboard/Main', [
            'total_employees' => User::where('role', 'employee')
                ->whereProvince(auth()->user()->province_id)->count(),
        ]);
    }

    private function employee_dashboard()
    {
        $user     = auth()->user();
        $province = $user->province?->name;

        return inertia('Employee/Dashboard/Main', [
            'my_province' => $province,
            ...self::build_ranking_payload(),
        ]);
    }

    private function regional_admin_dashboard()
    {
        $user        = auth()->user();
        $region      = Region::with('provinces')->findOrFail($user->region_id);
        $provinceIds = $region->provinces->pluck('id')->toArray();
        $payload     = self::build_ranking_payload($provinceIds);

        $latestYear      = $payload['available_years'][0] ?? null;
        $activeProvinces = $latestYear
            ? DB::table('provincial_director_kpis as pk')
                ->join('users as u', 'u.id', '=', 'pk.provincial_director_id')
                ->join('user_province as up', 'up.user_id', '=', 'u.id')
                ->where('pk.year', $latestYear)
                ->whereIn('up.province_id', $provinceIds)
                ->distinct()->count('up.province_id')
            : 0;

        return inertia('Admin/Dashboard/Main', [
            'region_name'                 => $region->name,
            'total_provinces'             => count($provinceIds),
            'total_users'                 => User::whereProvinceIn($provinceIds)->count(),
            'total_directors'             => User::where('role', 'provincial_director')->whereProvinceIn($provinceIds)->count(),
            'total_employees'             => User::where('role', 'employee')->whereProvinceIn($provinceIds)->count(),
            'active_reporting_provinces'  => $activeProvinces,
            ...$payload,
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
