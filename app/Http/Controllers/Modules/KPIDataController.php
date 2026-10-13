<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\KpiReportingDeadline;
use App\Services\RankingService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

// Super Admin + Sub Admin list of every province's KPI data, plus the quarterly
// reporting deadlines. Opening a province goes to the same quarterly entry
// screen the provincial admin uses (KpiEntryController). The KPI catalog itself
// — name, category, weight, roll-up rule — is managed at /kpi-catalog.
class KPIDataController extends Controller
{
    public function index()
    {
        $directorNameSql = "CONCAT_WS(' ',
            NULLIF(TRIM(IFNULL(pr.first_name,'')), ''),
            NULLIF(TRIM(IFNULL(pr.middle_name,'')), ''),
            NULLIF(TRIM(IFNULL(pr.last_name,'')), '')
        )";

        $directors = DB::table('user_province as up')
            ->join('users as u', 'u.id', '=', 'up.user_id')
            ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->where('r.name', 'provincial_director')
            ->where('ur.locked', true)
            ->select('up.province_id', 'up.user_id as director_id');

        $provinces = DB::table('provinces as p')
            ->leftJoinSub($directors, 'd', 'd.province_id', '=', 'p.id')
            ->leftJoin('users as u', 'u.id', '=', 'd.director_id')
            ->leftJoin('profiles as pr', 'pr.user_id', '=', 'u.id')
            ->select(
                'p.id', 'p.name', 'p.category',
                'u.id as director_id',
                DB::raw("$directorNameSql as director_name")
            )
            ->orderBy('p.name')->get();

        $yearsByProvince = DB::table('provincial_director_kpis')
            ->whereNotNull('province_id')
            ->select('province_id', DB::raw('GROUP_CONCAT(DISTINCT year ORDER BY year DESC) as years'))
            ->groupBy('province_id')->pluck('years', 'province_id');

        $list = $provinces->map(function ($p) use ($yearsByProvince) {
            $years = isset($yearsByProvince[$p->id])
                ? array_values(array_filter(explode(',', $yearsByProvince[$p->id])))
                : [];

            return [
                'id'          => Crypt::encrypt($p->id),
                'name'        => $p->name,
                'category'    => $p->category,
                'director_id' => $p->director_id,
                'director'    => $p->director_id ? (trim($p->director_name) ?: '—') : null,
                'years'       => $years,
                'years_count' => count($years),
            ];
        })->values();

        $availableYears = (new RankingService())->availableYears();

        // Deadlines can be set for any year with data, this year, and next.
        $deadlineYears = collect($availableYears)->map(fn($y) => (int) $y)
            ->push((int) now()->year, (int) now()->year + 1)
            ->unique()->sortDesc()->values();

        $deadlines = KpiReportingDeadline::all()
            ->groupBy('year')
            ->map(fn($rows) => $rows->mapWithKeys(fn($d) => [$d->quarter => $d->deadline->toDateString()]));

        return inertia('Admin/KpiData/Main', [
            'provinces'       => $list,
            'available_years' => $availableYears,
            'deadline_years'  => $deadlineYears,
            'deadlines'       => $deadlines,
        ]);
    }
}
