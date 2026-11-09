<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\KpiCorrectionRequest;
use App\Models\Region;
use App\Services\KpiCorrectionService;
use Illuminate\Http\Request;

// Correction requests for locked KPI periods. The provincial admin files and
// resubmits them from the KPI Entry page; the regional director forwards or
// returns them and the super admin approves or declines them on the Correction
// Requests page. The steps themselves live in KpiCorrectionService.
class KpiCorrectionController extends Controller
{
    public function __construct(private KpiCorrectionService $corrections) {}

    // Regional director (their region) and super admin (everything).
    public function index(Request $request)
    {
        $user = $request->user();

        return inertia('Kpi/Corrections', [
            'requests' => $this->corrections->visibleTo($user)
                ->map(fn($c) => KpiCorrectionService::present($c))->values(),
            'mode'     => $user->role === 'super_admin' ? 'central' : 'regional',
            'region'   => $user->role === 'regional_director' ? Region::find($user->region_id)?->name : null,
        ]);
    }

    // ── Provincial admin ──────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'year'    => ['required', 'integer', 'between:2000,2100'],
            'quarter' => ['nullable', 'integer', 'between:1,4'],
            'reason'  => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $provinceId = $request->user()->province_id ?? abort(403, 'Your account is not assigned to a province.');

        $correction = $this->corrections->file(
            $request->user(), $provinceId, (int) $data['year'],
            isset($data['quarter']) ? (int) $data['quarter'] : null, trim($data['reason']),
        );

        return back()->with('success', "Correction request for {$correction->periodLabel()} sent to your regional director.");
    }

    public function resubmit(Request $request, KpiCorrectionRequest $correction)
    {
        abort_unless((int) $correction->province_id === (int) $request->user()->province_id, 403);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);

        $this->corrections->resubmit($correction, $request->user(), trim($data['reason']));

        return back()->with('success', "Correction request for {$correction->periodLabel()} resubmitted.");
    }

    // ── Regional director ─────────────────────────────────────────────────

    public function forward(Request $request, KpiCorrectionRequest $correction)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $note = isset($data['note']) ? trim($data['note']) : null;

        $this->corrections->forward($correction, $request->user(), $note === '' ? null : $note);

        return back()->with('success', "Forwarded {$correction->province->name}'s request for {$correction->periodLabel()} to the central office.");
    }

    public function returnToProvince(Request $request, KpiCorrectionRequest $correction)
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:5', 'max:2000']]);

        $this->corrections->returnToProvince($correction, $request->user(), trim($data['note']));

        return back()->with('success', "Returned {$correction->province->name}'s request for {$correction->periodLabel()}.");
    }

    // ── Super admin ───────────────────────────────────────────────────────

    public function approve(Request $request, KpiCorrectionRequest $correction)
    {
        $data = $request->validate([
            'open_until' => ['required', 'date', 'after_or_equal:today'],
            'note'       => ['nullable', 'string', 'max:2000'],
        ]);
        $note = isset($data['note']) ? trim($data['note']) : null;

        $this->corrections->approve($correction, $request->user(), $data['open_until'], $note === '' ? null : $note);

        return back()->with('success', "Approved: {$correction->province->name} {$correction->periodLabel()} is open until " . $correction->fresh()->open_until->format('M j, Y') . '.');
    }

    public function decline(Request $request, KpiCorrectionRequest $correction)
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:5', 'max:2000']]);

        $this->corrections->decline($correction, $request->user(), trim($data['note']));

        return back()->with('success', "Declined {$correction->province->name}'s request for {$correction->periodLabel()}.");
    }
}
