<?php

namespace App\Http\Controllers\Modules\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Region;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    private function scopeToUser($query)
    {
        $user = auth()->user();

        if ($user->role === 'regional_admin') {
            $region      = Region::with('provinces')->findOrFail($user->region_id);
            $provinceIds = $region->provinces->pluck('id')->toArray();
            return $query->whereHas('user', fn($q) => $q->whereProvinceIn($provinceIds));
        }

        return $query->whereHas('user', function ($q) use ($user) {
            if ($user->province_id) {
                $q->whereProvince($user->province_id);
            }
        });
    }

    public function index()
    {
        $logs = $this->scopeToUser(ActivityLog::with('user.profile'))
                           ->latest('created_at')
                           ->paginate(20);

        return inertia('Other/ActivityLogs/Main', [
            'logs' => ActivityLogResource::collection($logs)->response()->getData(true),
        ]);
    }

    public function generate_logs_report(Request $request)
    {
        $request->validate([
            'date_from' => ['required', 'date'],
            'date_to'   => ['required', 'date', 'after_or_equal:date_from'],
            'type'      => ['nullable', 'string'],
        ]);

        // Prevent Inertia from intercepting the response
        abort_if($request->header('X-Inertia'), 422, 'Use direct GET request for file downloads.');

        $query = $this->scopeToUser(ActivityLog::with('user.profile'))
            ->whereDate('created_at', '>=', $request->date_from)
            ->whereDate('created_at', '<=', $request->date_to)
            ->latest('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $logs = $query->get()->map(function ($log) {
            $profile    = $log->user?->profile;
            $firstName  = $profile?->first_name ?? '';
            $middleName = $profile?->middle_name ? $profile->middle_name . ' ' : '';
            $lastName   = $profile?->last_name  ?? '';

            return [
                'name'        => trim("{$firstName} {$middleName}{$lastName}") ?: '—',
                'employee_id' => $log->user?->dost_employee_id ?? '—',
                'role'        => $log->user?->role ?? '—',
                'type'        => $log->type,
                'description' => $log->description,
                'created_at'  => $log->created_at?->format('M d, Y g:i A'),
            ];
        });

        $data = [
            'logs'      => $logs,
            'date_from' => \Carbon\Carbon::parse($request->date_from)->format('F d, Y'),
            'date_to'   => \Carbon\Carbon::parse($request->date_to)->format('F d, Y'),
            'type'      => $request->type ?? 'All',
            'generated' => now()->format('F d, Y g:i A'),
            'generated_by' => auth()->user()?->username ?? 'System',
        ];

        $pdf = Pdf::loadView('reports.activity-logs-report', $data)
                  ->setPaper('a4', 'landscape');

        $filename = 'activity-logs-' . now()->format('Y-m-d_H-i') . '.pdf';

        return $pdf->download($filename);
    }
}
