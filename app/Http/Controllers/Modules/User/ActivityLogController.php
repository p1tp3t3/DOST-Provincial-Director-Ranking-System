<?php

namespace App\Http\Controllers\Modules\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index() {
        return inertia('Admin/ActivityLogs/Main');
    }

    public function generate_logs_report(Request $request) {
        
    }
}
