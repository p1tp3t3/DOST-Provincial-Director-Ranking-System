<?php

namespace App\Http\Controllers\Modules\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubAdminReportController extends Controller
{
    public function index() {
        return inertia("SubAdmin/Report/Main");
    }
}
