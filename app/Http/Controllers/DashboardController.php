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

        $allRows = DB::table('provincial_director_kpis as pk')
            ->join('users as u',        'u.id',   '=', 'pk.provincial_director_id')
            ->join('provinces as p',    'p.id',   '=', 'u.province_id')
            ->join('profiles as pr',    'pr.user_id', '=', 'u.id')
            ->join('kpi_subrows as ks', 'ks.id',  '=', 'pk.kpi_subrow_id')
            ->whereIn('pk.year', $availableYears)
            ->whereNotNull('pk.target')
            ->whereNotNull('pk.accomplished')
            ->whereRaw("pk.target       REGEXP '^-?[0-9]+(\\.[0-9]+)?$'")
            ->whereRaw("pk.accomplished REGEXP '^-?[0-9]+(\\.[0-9]+)?$'")
            ->whereRaw("CAST(pk.target AS DECIMAL(20,4)) > 0")
            ->select(
                'pk.year',
                'ks.kpi_id',
                'p.name as province',
                'p.category',
                DB::raw("CONCAT(pr.first_name, ' ', pr.last_name) as director"),
                DB::raw("CAST(pk.accomplished AS DECIMAL(20,4)) as accomplished"),
                DB::raw("CAST(pk.target       AS DECIMAL(20,4)) as target")
            )
            ->get();

        $grouped = [];
        foreach ($allRows as $row) {
            $y = $row->year; $prov = $row->province;
            if (!isset($grouped[$y][$prov])) {
                $grouped[$y][$prov] = [
                    'province'       => $prov,
                    'category'       => $row->category,
                    'director'       => $row->director,
                    'overall_ratios' => [],
                    'kpi_ratios'     => [],
                ];
            }
            $ratio = min(($row->accomplished / $row->target) * 100, 200);
            $grouped[$y][$prov]['overall_ratios'][] = $ratio;
            $grouped[$y][$prov]['kpi_ratios'][$row->kpi_id][] = $ratio;
        }

        $kpiScoresByYear = [];
        foreach ($availableYears as $year) {
            $yearData = $grouped[$year] ?? [];
            $overall  = self::build_scores($yearData, 'overall_ratios');
            $perKpi   = [];
            foreach ($kpiOutcomes as $kpi) {
                $kpiId = $kpi['id']; $kpiScores = [];
                foreach ($yearData as $data) {
                    $ratios = $data['kpi_ratios'][$kpiId] ?? [];
                    if (!$ratios) continue;
                    $kpiScores[] = [
                        'province' => $data['province'],
                        'category' => $data['category'],
                        'director' => $data['director'],
                        'score'    => round(array_sum($ratios) / count($ratios), 1),
                        'count'    => count($ratios),
                    ];
                }
                usort($kpiScores, fn($a, $b) => $b['score'] <=> $a['score']);
                $perKpi[$kpiId] = array_values($kpiScores);
            }
            $kpiScoresByYear[$year] = ['overall' => $overall, 'kpi' => $perKpi];
        }

        return [
            'kpi_scores_by_year' => $kpiScoresByYear,
            'kpi_outcomes'       => $kpiOutcomes,
            'available_years'    => $availableYears,
        ];
    }

    private static function build_scores(array $yearData, string $ratioKey): array
    {
        $scores = [];
        foreach ($yearData as $data) {
            $ratios = $data[$ratioKey] ?? [];
            if (!$ratios) continue;
            $scores[] = [
                'province' => $data['province'],
                'category' => $data['category'],
                'director' => $data['director'],
                'score'    => round(array_sum($ratios) / count($ratios), 1),
                'count'    => count($ratios),
            ];
        }
        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_values($scores);
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

    private function employee_dashboard() {
        return inertia('Employee/Dashboard/Main', [
            // any employee-specific data can go here
        ]);
    }
}
