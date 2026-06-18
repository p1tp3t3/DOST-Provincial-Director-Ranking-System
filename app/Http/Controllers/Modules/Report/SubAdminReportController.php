<?php

namespace App\Http\Controllers\Modules\Report;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Province;
use App\Models\User;
use App\Services\RankingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubAdminReportController extends Controller
{
    public function index()
    {
        return inertia('SubAdmin/Report/Main', [
            'province_stats'      => $this->get_province_stats(),
            'user_stats'          => $this->get_user_stats(),
            'director_rankings'   => $this->get_director_rankings(),
            'log_stats'           => $this->get_log_stats(),
            'recent_logs'         => $this->get_recent_logs(),
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

        $pdf = Pdf::loadView('reports.sub-admin-report', [
            'province_stats'    => $this->get_province_stats(),
            'user_stats'        => $this->get_user_stats(),
            'director_rankings' => $this->get_director_rankings(10),
            'log_stats'         => $this->get_log_stats($dateFrom, $dateTo),
            'recent_logs'       => $this->get_recent_logs(15, $dateFrom, $dateTo),
            'date_from'         => $dateFrom->format('F d, Y'),
            'date_to'           => $dateTo->format('F d, Y'),
            'generated'         => now()->format('F d, Y g:i A'),
            'generated_by'      => Auth::user()?->username ?? 'System',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('sub-admin-report-' . now()->format('Y-m-d') . '.pdf');
    }

    // ── Helpers ───────────────────────────────────────────────

    private function get_province_stats(): array
    {
        return Province::with(['directorAssignments.profile'])->get()->map(function ($p) {
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

    private function get_user_stats(): array
    {
        $counts = User::selectRaw('role, COUNT(*) as count')
                      ->groupBy('role')
                      ->pluck('count', 'role');

        return [
            'total'                => User::count(),
            'provincial_admin'     => $counts['provincial_admin']     ?? 0,
            'provincial_director'  => $counts['provincial_director']  ?? 0,
            'employee'             => $counts['employee']             ?? 0,
            'provinces'            => Province::count(),
        ];
    }

    // Rankings come from the official PSTD Ranking Matrix weighted score (latest reporting
    // year). Each row carries its tier rank + bucket (Top / Average / Under) so the
    // PDF can present the same view the dashboard uses.
    private function get_director_rankings(int $limit = 8): array
    {
        $svc   = new RankingService();
        $years = $svc->availableYears();
        if (empty($years)) return [];
        $latestYear = $years[0];

        $rows = [];
        foreach ($svc->rankByYear($latestYear) as $tier => $tierRows) {
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

    private function get_log_stats(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = ActivityLog::selectRaw('type, COUNT(*) as count')->groupBy('type');
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

    private function get_recent_logs(int $limit = 8, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = ActivityLog::with('user.profile')->latest('created_at')->limit($limit);
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
