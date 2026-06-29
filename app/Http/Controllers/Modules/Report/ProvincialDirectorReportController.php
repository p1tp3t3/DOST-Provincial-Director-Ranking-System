<?php

namespace App\Http\Controllers\Modules\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProvincialDirectorReportController extends Controller
{
    public function index() {
        return inertia('ProvincialDirector/Report/Main');
    }

    public function export() {

    }
}
