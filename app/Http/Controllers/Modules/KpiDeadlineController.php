<?php

namespace App\Http\Controllers\Modules;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\KpiReportingDeadline;
use Illuminate\Http\Request;

// Central office sets the last day provinces can enter each quarter's figures.
// Past it, a quarter is locked; the Q4 deadline also locks the year's targets.
// Clearing a date removes the deadline, leaving that quarter open.
class KpiDeadlineController extends Controller
{
    public function update(Request $request, int $year)
    {
        $data = $request->validate([
            'deadlines'   => ['present', 'array'],
            'deadlines.*' => ['nullable', 'date'],
        ]);

        foreach ([1, 2, 3, 4] as $quarter) {
            $date = $data['deadlines'][$quarter] ?? null;
            $key  = ['year' => $year, 'quarter' => $quarter];

            if ($date) {
                KpiReportingDeadline::updateOrCreate($key, ['deadline' => $date, 'set_by' => $request->user()->id]);
            } else {
                KpiReportingDeadline::where($key)->delete();
            }
        }

        ActivityLogHelper::setReportingDeadlines($year);

        return back()->with('success', "Saved the {$year} reporting deadlines.");
    }
}
