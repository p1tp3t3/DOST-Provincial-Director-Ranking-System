<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\KpiEditRequest;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Lets a regional admin review provincial admins' requests for extra KPI edit
// access — granted once a province has already used its free edit for a given
// (director, year).
class KpiEditAccessController extends Controller
{
    public function index()
    {
        $requests = $this->regionRequests()
            ->with(['director.profile', 'director.provinces', 'requester'])
            ->latest()
            ->get()
            ->map(fn (KpiEditRequest $r) => [
                'id'            => $r->id,
                'director_name' => $this->fullName($r->director),
                'province'      => $r->director->provinces->first()?->name ?? '—',
                'requested_by'  => $r->requester?->username ?? '—',
                'year'          => $r->year,
                'reason'        => $r->reason,
                'status'        => $r->status,
                'created_at'    => $r->created_at->toIso8601String(),
                'reviewed_at'   => $r->reviewed_at?->toIso8601String(),
                'response_note' => $r->response_note,
                'used'          => $r->used_at !== null,
            ]);

        return inertia('RegionalAdmin/KpiEditRequests/Main', [
            'requests' => $requests,
        ]);
    }

    public function approve(int $id)
    {
        $request = $this->regionRequests()->where('status', 'pending')->findOrFail($id);

        $request->update([
            'status'      => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Request approved.');
    }

    public function reject(Request $httpRequest, int $id)
    {
        $data = $httpRequest->validate([
            'response_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $request = $this->regionRequests()->where('status', 'pending')->findOrFail($id);

        $request->update([
            'status'        => 'rejected',
            'reviewed_by'   => Auth::id(),
            'reviewed_at'   => now(),
            'response_note' => $data['response_note'] ?? null,
        ]);

        return back()->with('success', 'Request rejected.');
    }

    private function regionRequests()
    {
        $region      = Region::with('provinces')->findOrFail(Auth::user()->region_id);
        $provinceIds = $region->provinces->pluck('id')->toArray();
        $directorIds = User::whereProvinceIn($provinceIds)->where('role', 'provincial_director')->pluck('id');

        return KpiEditRequest::whereIn('provincial_director_id', $directorIds);
    }

    private function fullName(User $user): string
    {
        $profile = $user->profile;
        if (!$profile) return '—';

        return trim(($profile->first_name ?? '') . ' ' . ($profile->middle_name ?? '') . ' ' . ($profile->last_name ?? '')) ?: '—';
    }
}
