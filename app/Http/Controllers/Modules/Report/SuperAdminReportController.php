<?php

namespace App\Http\Controllers\Modules\Report;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SuperAdminReportController extends Controller
{
    public function index()
    {
        return inertia('Admin/Report/Main', [
            'user_stats'   => $this->get_user_stats(),
            'recent_users' => $this->get_recent_users(),
            'recent_logs'  => $this->get_recent_logs(),
            'log_stats'    => $this->get_log_stats(),
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

        #ActivityLogHelper::exportReport('System Report (Users & Logs)');

        $pdf = Pdf::loadView('reports.system-report', [
            'user_stats'   => $this->get_user_stats(),
            'role_counts'  => $this->get_role_counts(),
            'log_stats'    => $this->get_log_stats($dateFrom, $dateTo),
            'recent_logs'  => $this->get_recent_logs(20, $dateFrom, $dateTo),
            'recent_users' => $this->get_recent_users(10),
            'date_from'    => $dateFrom->format('F d, Y'),
            'date_to'      => $dateTo->format('F d, Y'),
            'generated'    => now()->format('F d, Y g:i A'),
            'generated_by' => Auth::user()?->username ?? 'System',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('system-report-' . now()->format('Y-m-d') . '.pdf');
    }

    // ── Helpers ───────────────────────────────────────────────

    private function get_user_stats(): array
    {
        $counts = User::selectRaw('role, COUNT(*) as count')
                      ->groupBy('role')
                      ->pluck('count', 'role');

        return [
            'total'                => User::count(),
            'super_admin'          => $counts['super_admin']          ?? 0,
            'sub_admin'            => $counts['sub_admin']            ?? 0,
            'provincial_admin'     => $counts['provincial_admin']     ?? 0,
            'provincial_sub_admin' => $counts['provincial_sub_admin'] ?? 0,
            'provincial_director'  => $counts['provincial_director']  ?? 0,
            'employee'             => $counts['employee']             ?? 0,
        ];
    }

    private function get_role_counts(): array
    {
        return User::selectRaw('role, COUNT(*) as count')
                   ->groupBy('role')
                   ->pluck('count', 'role')
                   ->toArray();
    }

    private function get_recent_users(int $limit = 6): array
    {
        return User::with(['profile', 'provinces'])
                   ->latest('created_at')
                   ->limit($limit)
                   ->get()
                   ->map(fn($u) => [
                       'id'         => $u->id,
                       'name'       => trim(($u->profile?->first_name ?? '') . ' ' . ($u->profile?->last_name ?? '')),
                       'email'      => $u->email,
                       'role'       => $u->role,
                       'province'   => $u->province?->name,
                       'registered' => $u->created_at?->format('M d, Y'),
                   ])->toArray();
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
