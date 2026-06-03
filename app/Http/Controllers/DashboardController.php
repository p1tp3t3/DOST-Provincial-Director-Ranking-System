<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        switch(auth()->user()->role) {
            case 'super_admin':
                return self::super_admin_dashboard();
            case 'sub_admin':
                return self::sub_admin_dashboard();
            case 'provincial_admin':
                return self::provincial_admin_dashboard();
            case 'provincial_sub_admin':
                return self::provincial_sub_admin_dashboard();
            case 'provincial_director':
                return self::director_dashboard();
            case 'employee':
                return self::employee_dashboard();
        }
    }

    private function super_admin_dashboard()
    {
        $kpiData = self::build_kpi_data();

        return inertia('Admin/Dashboard/Main', [
            'total_provinces'    => Province::count(),
            'total_users'        => User::count(),
            'total_directors'    => User::where('role', 'provincial_director')->count(),
            'total_employees'    => User::where('role', 'employee')->count(),
            'total_sub_admins'   => User::where('role', 'sub_admin')->count(),
            ...$kpiData,
        ]);
    }

    public function map_index()
    {
        return inertia('Admin/Map/Main', self::build_kpi_data());
    }

    private static function build_kpi_data(): array
    {
        $availableYears = DB::table('provincial_director_kpis')
            ->distinct()->orderByDesc('year')->pluck('year')->values()->toArray();

        $kpiOutcomes = DB::table('kpis')->orderBy('id')->get(['id', 'outcome_title'])
            ->map(fn($k) => ['id' => $k->id, 'title' => $k->outcome_title])
            ->values()->toArray();

        // Total subrows defined per KPI; sum = 54 (used as Operational/Absolute denominator)
        $kpiSubrowCounts = DB::table('kpi_subrows')
            ->select('kpi_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('kpi_id')->pluck('cnt', 'kpi_id')->toArray();
        $totalSubrows = array_sum($kpiSubrowCounts);

        // Pull every (province, year, subrow) tuple. We do not pre-filter by numeric
        // target here because non-numeric values (e.g., "No Target") are meaningful
        // for the "tracked" vs "active target" distinction.
        $allRows = DB::table('provincial_director_kpis as pk')
            ->join('users as u',        'u.id',   '=', 'pk.provincial_director_id')
            ->join('provinces as p',    'p.id',   '=', 'u.province_id')
            ->join('profiles as pr',    'pr.user_id', '=', 'u.id')
            ->join('kpi_subrows as ks', 'ks.id',  '=', 'pk.kpi_subrow_id')
            ->whereIn('pk.year', $availableYears)
            ->select(
                'pk.year', 'ks.kpi_id', 'ks.id as subrow_id',
                'p.name as province', 'p.category',
                DB::raw("CONCAT(pr.first_name, ' ', pr.last_name) as director"),
                'pk.target', 'pk.accomplished'
            )->get();

        // For each (province, year): count met / exceeded / active per KPI and overall
        $grouped = [];
        foreach ($allRows as $row) {
            $y = $row->year; $prov = $row->province;
            if (!isset($grouped[$y][$prov])) {
                $grouped[$y][$prov] = [
                    'province'         => $prov,
                    'category'         => $row->category,
                    'director'         => $row->director,
                    'kpi_met'          => [],   // [kpi_id => count of met subrows  (acc>=target)]
                    'kpi_exceeded'     => [],   // [kpi_id => count of exceeded     (acc>target)]
                    'kpi_active'       => [],   // [kpi_id => count of active targets (target>0)]
                    'overall_met'      => 0,
                    'overall_exceeded' => 0,
                    'overall_active'   => 0,
                ];
            }
            $target = self::numeric_or_null($row->target);
            $acc    = self::numeric_or_null($row->accomplished);
            $hasActiveTarget = $target !== null && $target > 0;
            if (!$hasActiveTarget) continue;

            $isMet      = $acc !== null && $acc >= $target;
            $isExceeded = $acc !== null && $acc >  $target;
            $kid = $row->kpi_id;

            $grouped[$y][$prov]['kpi_active'][$kid] = ($grouped[$y][$prov]['kpi_active'][$kid] ?? 0) + 1;
            $grouped[$y][$prov]['overall_active']++;
            if ($isMet) {
                $grouped[$y][$prov]['kpi_met'][$kid] = ($grouped[$y][$prov]['kpi_met'][$kid] ?? 0) + 1;
                $grouped[$y][$prov]['overall_met']++;
            }
            if ($isExceeded) {
                $grouped[$y][$prov]['kpi_exceeded'][$kid] = ($grouped[$y][$prov]['kpi_exceeded'][$kid] ?? 0) + 1;
                $grouped[$y][$prov]['overall_exceeded']++;
            }
        }

        $kpiScoresByYear = [];
        foreach ($availableYears as $year) {
            $yearData = $grouped[$year] ?? [];

            // Overall rankings — denominator is 54
            $overall = [];
            foreach ($yearData as $data) {
                $overall[] = self::score_record(
                    $data, $data['overall_met'], $data['overall_exceeded'],
                    $data['overall_active'], $totalSubrows
                );
            }
            usort($overall, fn($a, $b) => $b['operational_score'] <=> $a['operational_score']);

            // Per-KPI rankings — denominator is that KPI's subrow count
            $perKpi = [];
            foreach ($kpiOutcomes as $kpi) {
                $kpiId = $kpi['id'];
                $denom = max($kpiSubrowCounts[$kpiId] ?? 1, 1);
                $rows  = [];
                foreach ($yearData as $data) {
                    $met      = $data['kpi_met'][$kpiId]      ?? 0;
                    $exceeded = $data['kpi_exceeded'][$kpiId] ?? 0;
                    $active   = $data['kpi_active'][$kpiId]   ?? 0;
                    $rows[]   = self::score_record($data, $met, $exceeded, $active, $denom);
                }
                usort($rows, fn($a, $b) => $b['operational_score'] <=> $a['operational_score']);
                $perKpi[$kpiId] = $rows;
            }
            $kpiScoresByYear[$year] = ['overall' => $overall, 'kpi' => $perKpi];
        }

        return [
            'kpi_scores_by_year' => $kpiScoresByYear,
            'kpi_outcomes'       => $kpiOutcomes,
            'available_years'    => $availableYears,
        ];
    }

    // Builds one ranking row with all four evaluation scores.
    //   Strict      = met / active_target              (skill at hitting goals set)
    //   Operational = active_target / total_subrows    (coverage of the board)
    //   Absolute    = met / total_subrows              (harshest: blanks count as misses)
    //   Excellence  = exceeded / active_target         (over-achievement on what you set)
    private static function score_record(array $data, int $met, int $exceeded, int $active, int $total): array
    {
        return [
            'province'          => $data['province'],
            'category'          => $data['category'],
            'director'          => $data['director'],
            'met'               => $met,
            'exceeded'          => $exceeded,
            'active'            => $active,
            'total'             => $total,
            'strict_score'      => $active > 0 ? round($met      / $active * 100, 2) : 0,
            'operational_score' => $total  > 0 ? round($active   / $total  * 100, 2) : 0,
            'absolute_score'    => $total  > 0 ? round($met      / $total  * 100, 2) : 0,
            'excellence_score'  => $active > 0 ? round($exceeded / $active * 100, 2) : 0,
        ];
    }

    // Parses a DB string value into a number, or null if blank/non-numeric.
    // "No Target", "-", "n/a", "" all return null.
    private static function numeric_or_null($v): ?float
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        // Allow numeric strings with optional decimal/negative
        if (!preg_match('/^-?\d+(\.\d+)?$/', $s)) return null;
        return (float)$s;
    }

    private function sub_admin_dashboard()
    {
        return inertia('SubAdmin/Dashboard/Main', [
            'total_directors' => User::where('role', 'provincial_director')->count(),
            'total_employees' => User::where('role', 'employee')->count(),
            'total_provinces' => Province::count()
        ]);
    }

    private function provincial_admin_dashboard()
    {
        return inertia('ProvincialAdmin/Dashboard/Main', [
            'total_employees' => User::where('role', 'employee')
                                     ->where('province_id', auth()->user()->province_id)
                                     ->count(),
        ]);
    }

    private function provincial_sub_admin_dashboard()
    {
        return inertia('ProvincialSubAdmin/Dashboard/Main', [
            'total_employees' => User::where('role', 'employee')
                                     ->where('province_id', auth()->user()->province_id)
                                     ->count(),
        ]);
    }

    private function director_dashboard()
    {
        return inertia('ProvincialDirector/Dasbboard/Main', [
            'total_employees' => User::where('role', 'employee')
                                     ->where('province_id', auth()->user()->province_id)
                                     ->count(),
        ]);
    }

    private function employee_dashboard()
    {
        $user = auth()->user();
        $province = $user->province?->name;

        $kpiData = self::build_kpi_data();

        return inertia('Employee/Dashboard/Main', [
            'my_province' => $province,
            ...$kpiData,
        ]);
    }
}
