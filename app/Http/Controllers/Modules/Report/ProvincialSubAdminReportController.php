<?php

namespace App\Http\Controllers\Modules\Report;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Province;
use App\Models\ProvincialDirectorKPI;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ProvincialSubAdminReportController extends Controller
{
    public function index()
    {
        $provinceId = Auth::user()->province_id;

        return inertia('ProvincialSubAdmin/Report/Main', [
            'province'       => $this->get_province($provinceId),
            'user_stats'     => $this->get_user_stats($provinceId),
            'employee_list'  => $this->get_employee_list($provinceId),
            'director_kpi'   => $this->get_director_kpi($provinceId),
            'log_stats'      => $this->get_log_stats($provinceId),
            'recent_logs'    => $this->get_recent_logs($provinceId),
        ]);
    }

    public function export()
    {
        $provinceId = Auth::user()->province_id;

        $pdf = Pdf::loadView('reports.provincial-sub-admin-report', [
            'province'      => $this->get_province($provinceId),
            'user_stats'    => $this->get_user_stats($provinceId),
            'employee_list' => $this->get_employee_list($provinceId, 30),
            'director_kpi'  => $this->get_director_kpi($provinceId),
            'log_stats'     => $this->get_log_stats($provinceId),
            'recent_logs'   => $this->get_recent_logs($provinceId, 15),
            'generated'     => now()->format('F d, Y g:i A'),
            'generated_by'  => Auth::user()?->username ?? 'System',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('provincial-report-' . now()->format('Y-m-d') . '.pdf');
    }

    // ── Helpers ───────────────────────────────────────────────

    private function get_province(?int $provinceId): array
    {
        if (!$provinceId) return [];

        $p = Province::find($provinceId);
        if (!$p) return [];

        $director = User::where('province_id', $provinceId)
            ->where('role', 'provincial_director')
            ->with('profile')
            ->first();

        $dirName = $director
            ? trim(($director->profile?->first_name ?? '') . ' ' . ($director->profile?->last_name ?? ''))
            : '—';

        return [
            'id'                   => $p->id,
            'name'                 => $p->name,
            'category'             => $p->category,
            'num_municipalities'   => $p->num_municipalities,
            'num_cities'           => $p->num_cities,
            'num_plantilla'        => $p->num_plantilla_employees,
            'director'             => $dirName ?: '—',
        ];
    }

    private function get_user_stats(?int $provinceId): array
    {
        if (!$provinceId) return [];

        $users = User::where('province_id', $provinceId)->get();

        $byRole    = $users->groupBy('role')->map->count();
        $employees = User::where('province_id', $provinceId)
            ->where('role', 'employee')
            ->with('profile.employeeProfile')
            ->get();

        $byStatus = $employees->groupBy(fn($u) => $u->profile?->employeeProfile?->status ?? 'unknown')->map->count();

        return [
            'total'            => $users->count(),
            'employees'        => $byRole['employee']           ?? 0,
            'admins'           => $byRole['provincial_admin']   ?? 0,
            'directors'        => $byRole['provincial_director']?? 0,
            'permanent'        => $byStatus['permanent']        ?? 0,
            'cos'              => $byStatus['cos']              ?? 0,
            'jo'               => $byStatus['jo']               ?? 0,
        ];
    }

    private function get_employee_list(?int $provinceId, int $limit = 10): array
    {
        if (!$provinceId) return [];

        return User::where('province_id', $provinceId)
            ->where('role', 'employee')
            ->with(['profile.employeeProfile'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($u) => [
                'id'               => $u->id,
                'name'             => trim(($u->profile?->first_name ?? '') . ' ' . ($u->profile?->last_name ?? '')),
                'dost_id'          => $u->dost_employee_id ?? '—',
                'position'         => $u->profile?->employeeProfile?->position ?? '—',
                'status'           => $u->profile?->employeeProfile?->status   ?? '—',
                'length_of_service'=> $u->profile?->length_of_service          ?? '—',
            ])->toArray();
    }

    private function get_director_kpi(?int $provinceId): array
    {
        if (!$provinceId) return [];

        $director = User::where('province_id', $provinceId)
            ->where('role', 'provincial_director')
            ->first();

        if (!$director) return [];

        $toNum = fn($v) => (float) preg_replace('/[^0-9.]/', '', $v ?? '0');

        $kpis        = ProvincialDirectorKPI::where('provincial_director_id', $director->id)->get();
        $target      = $kpis->sum(fn($k) => $toNum($k->target));
        $accomplished = $kpis->sum(fn($k) => $toNum($k->accomplished));
        $rate        = $target > 0 ? round($accomplished / $target * 100, 1) : 0;

        return [
            'director_id'  => $director->id,
            'target'       => $target,
            'accomplished' => $accomplished,
            'rate'         => $rate,
            'total_items'  => $kpis->count(),
        ];
    }

    private function get_log_stats(?int $provinceId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $userIds = $provinceId
            ? User::where('province_id', $provinceId)->pluck('id')
            : collect();

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

    private function get_recent_logs(?int $provinceId, int $limit = 8): array
    {
        if (!$provinceId) return [];

        $userIds = User::where('province_id', $provinceId)->pluck('id');

        return ActivityLog::with('user.profile')
            ->whereIn('user_id', $userIds)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($log) => [
                'id'          => $log->id,
                'name'        => trim(($log->user?->profile?->first_name ?? '') . ' ' . ($log->user?->profile?->last_name ?? '')),
                'dost_id'     => $log->user?->dost_employee_id ?? '—',
                'role'        => $log->user?->role ?? '—',
                'type'        => $log->type,
                'description' => $log->description,
                'created_at'  => $log->created_at?->format('M d, Y g:i A'),
            ])->toArray();
    }
}
