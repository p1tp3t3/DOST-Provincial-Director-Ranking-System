<?php

namespace App\Http\Controllers\Modules\Report;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Region;
use App\Models\User;
use App\Services\RankingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegionalAdminReportController extends Controller
{
    public function index()
    {
        $region      = Region::with('provinces')->findOrFail(Auth::user()->region_id);
        $provinceIds = $region->provinces->pluck('id')->toArray();

        return inertia('RegionalAdmin/Report/Main', [
            'region'             => ['id' => $region->id, 'name' => $region->name, 'island_under' => $region->island_under],
            'province_stats'     => $this->get_province_stats($region),
            'user_stats'         => $this->get_user_stats($provinceIds),
            'director_rankings'  => $this->get_director_rankings($provinceIds),
            'log_stats'          => $this->get_log_stats($provinceIds),
            'recent_logs'        => $this->get_recent_logs($provinceIds),
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : now()->subMonth();
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : now()->endOfDay();

        $region      = Region::with('provinces')->findOrFail(Auth::user()->region_id);
        $provinceIds = $region->provinces->pluck('id')->toArray();

        $pdf = Pdf::loadView('reports.regional-admin-report', [
            'region'            => ['id' => $region->id, 'name' => $region->name, 'island_under' => $region->island_under],
            'province_stats'    => $this->get_province_stats($region),
            'user_stats'        => $this->get_user_stats($provinceIds),
            'director_rankings' => $this->get_director_rankings($provinceIds, 10),
            'log_stats'         => $this->get_log_stats($provinceIds, $dateFrom, $dateTo),
            'recent_logs'       => $this->get_recent_logs($provinceIds, 15, $dateFrom, $dateTo),
            'date_from'         => $dateFrom->format('F d, Y'),
            'date_to'           => $dateTo->format('F d, Y'),
            'generated'         => now()->format('F d, Y g:i A'),
            'generated_by'      => Auth::user()?->username ?? 'System',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('regional-admin-report-' . now()->format('Y-m-d') . '.pdf');
    }

    // ── Helpers ───────────────────────────────────────────────

    private function get_province_stats(Region $region): array
    {
        return $region->provinces()->with(['directorAssignments.profile'])->get()->map(function ($p) {
            $director = $p->provincialDirector;
            $name     = $director
                ? trim(($director->profile?->first_name ?? '') . ' ' . ($director->profile?->last_name ?? ''))
                : '—';

            return [
                'id'          => $p->id,
                'name'        => $p->name,
                'category'    => $p->category,
                'director'    => $name ?: '—',
                'employees'   => User::whereProvince($p->id)->where('role', 'employee')->count(),
                'admins'      => User::whereProvince($p->id)->where('role', 'provincial_admin')->count(),
                'total_users' => User::whereProvince($p->id)->count(),
            ];
        })->sortBy('name')->values()->toArray();
    }

    private function get_user_stats(array $provinceIds): array
    {
        $counts = User::whereProvinceIn($provinceIds)
            ->selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role');

        return [
            'total'                => User::whereProvinceIn($provinceIds)->count(),
            'provincial_admin'     => $counts['provincial_admin']     ?? 0,
            'provincial_director'  => $counts['provincial_director']  ?? 0,
            'provincial_sub_admin' => $counts['provincial_sub_admin'] ?? 0,
            'employee'             => $counts['employee']             ?? 0,
            'provinces'            => count($provinceIds),
        ];
    }

    // Rankings come from the official PSTD Ranking Matrix weighted score (latest reporting
    // year), restricted to provinces within this region.
    private function get_director_rankings(array $provinceIds, int $limit = 8): array
    {
        $svc   = new RankingService();
        $years = $svc->availableYears();
        if (empty($years)) return [];
        $latestYear = $years[0];

        $rows = [];
        foreach ($svc->rankByYear($latestYear, $provinceIds) as $tier => $tierRows) {
            foreach ($tierRows as $r) {
                $rows[] = [
                    'id'           => $r['province_id'],
                    'name'         => $r['director'] ?: '—',
                    'province'     => $r['province'],
                    'tier'         => $tier,
                    'tier_rank'    => $r['rank'],
                    'bucket'       => $r['bucket'],
                    'total_pct'    => $r['total_pct'],
                    'adjective'    => $r['adjective_label'],
                    'subtotals'    => $r['subtotals_pct'],
                    'year'         => $latestYear,
                ];
            }
        }

        usort($rows, fn($a, $b) => $b['total_pct'] <=> $a['total_pct']);
        return array_slice($rows, 0, $limit);
    }

    private function get_log_stats(array $provinceIds, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $userIds = User::whereProvinceIn($provinceIds)->pluck('id');

        $query = ActivityLog::selectRaw('type, COUNT(*) as count')
            ->whereIn('user_id', $userIds)
            ->groupBy('type');
        if ($from) $query->where('created_at', '>=', $from);
        if ($to)   $query->where('created_at', '<=', $to);

        $counts = $query->pluck('count', 'type');

        return [
            'login'    => $counts['login']    ?? 0,
            'logout'   => $counts['logout']   ?? 0,
            'create'   => $counts['create']   ?? 0,
            'update'   => $counts['update']   ?? 0,
            'delete'   => $counts['delete']   ?? 0,
            'generate' => $counts['generate'] ?? 0,
            'export'   => $counts['export']   ?? 0,
            'view'     => $counts['view']     ?? 0,
        ];
    }

    private function get_recent_logs(array $provinceIds, int $limit = 8, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $userIds = User::whereProvinceIn($provinceIds)->pluck('id');

        $query = ActivityLog::with('user.profile')
            ->whereIn('user_id', $userIds)
            ->latest('created_at')
            ->limit($limit);
        if ($from) $query->where('created_at', '>=', $from);
        if ($to)   $query->where('created_at', '<=', $to);

        return $query->get()->map(fn($log) => [
            'id'          => $log->id,
            'name'        => trim(($log->user?->profile?->first_name ?? '') . ' ' . ($log->user?->profile?->last_name ?? '')),
            'employee_id' => $log->user?->dost_employee_id ?? '—',
            'role'        => $log->user?->role ?? '—',
            'type'        => $log->type,
            'description' => $log->description,
            'created_at'  => $log->created_at?->format('M d, Y g:i A'),
        ])->toArray();
    }
}
