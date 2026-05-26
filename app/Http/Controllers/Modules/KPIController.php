<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\KPI;
use Illuminate\Http\Request;

class KPIController extends Controller
{
    public function index() {
        return inertia('Other/Director/KPI');
    }

    public function get_kpi() {
        return KPI::all();
    }
}
