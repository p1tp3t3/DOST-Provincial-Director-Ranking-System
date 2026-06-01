<?php

namespace App\Http\Controllers\Modules\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index() {
        $logs = ActivityLog::latest('created_at')->paginate(20);
        return inertia('Other/ActivityLogs/Main', [
            'logs' => ActivityLogResource::collection($logs)
        ]);
    }

    public function generate_logs_report(Request $request) {
        
    }
}
