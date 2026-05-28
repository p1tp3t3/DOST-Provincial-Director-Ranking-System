<?php

namespace App\Http\Controllers\Modules\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SuperAdminReportController extends Controller
{
    public function index() {
        return inertia('Admin/Report/Main');
    }
}
