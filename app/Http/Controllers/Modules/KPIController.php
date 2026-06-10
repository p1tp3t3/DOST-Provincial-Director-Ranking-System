<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\KPI;

class KPIController extends Controller
{
    public function director_index()
    {
        return inertia('Other/Director/KPI');
    }

    public function get_kpi()
    {
        return KPI::all();
    }
}
