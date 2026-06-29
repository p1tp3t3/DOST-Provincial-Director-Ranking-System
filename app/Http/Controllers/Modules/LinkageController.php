<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\KPI;
use App\Models\Linkage;
use App\Models\ProvincialDirectorKPI;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Evidence-based KPI module backing the `func_linkages_established` KPI.
// Adding/removing rows here is the only way to change the accomplishment count —
// the count is mirrored back into provincial_director_kpis.accomplished so
// existing scoring continues to work without changes.
class LinkageController extends Controller
{
    public function index(int $directorId, int $year)
    {
        $director = $this->authorizeDirector($directorId);

        $linkages = Linkage::where('provincial_director_id', $director->id)
            ->where('year', $year)
            ->orderByDesc('date_signed')
            ->orderByDesc('id')
            ->get()
            ->map(fn($r) => [
                'id'                   => $r->id,
                'partner_organization' => $r->partner_organization,
                'title'                => $r->title,
                'type'                 => $r->type,
                'date_signed'          => optional($r->date_signed)->format('Y-m-d'),
                'signatories'          => $r->signatories,
                'remarks'              => $r->remarks,
            ])->values();

        $profile      = $director->profile;
        $directorName = $profile
            ? trim(($profile->first_name ?? '') . ' ' . ($profile->middle_name ?? '') . ' ' . ($profile->last_name ?? ''))
            : '—';

        $returnUrl = $this->returnUrlFor($director->id, $year);

        return inertia('Modules/Linkages/Index', [
            'director'   => ['id' => $director->id, 'name' => $directorName ?: '—'],
            'year'       => $year,
            'linkages'   => $linkages,
            'count'      => $linkages->count(),
            'return_url' => $returnUrl,
        ]);
    }

    public function store(Request $request, int $directorId, int $year)
    {
        $director = $this->authorizeDirector($directorId);
        $data     = $this->validated($request);

        Linkage::create([
            'provincial_director_id' => $director->id,
            'year'                   => $year,
            'partner_organization'   => $data['partner_organization'],
            'title'                  => $data['title'],
            'type'                   => $data['type'],
            'date_signed'            => $data['date_signed'],
            'signatories'            => $data['signatories'] ?? null,
            'remarks'                => $data['remarks'] ?? null,
        ]);

        $this->syncAccomplishedCount($director->id, $year);

        return back()->with('success', 'Linkage added.');
    }

    public function update(Request $request, int $directorId, int $year, int $linkageId)
    {
        $director = $this->authorizeDirector($directorId);
        $linkage  = Linkage::where('provincial_director_id', $director->id)
            ->where('year', $year)
            ->findOrFail($linkageId);

        $data = $this->validated($request);
        $linkage->update($data);

        return back()->with('success', 'Linkage updated.');
    }

    public function destroy(int $directorId, int $year, int $linkageId)
    {
        $director = $this->authorizeDirector($directorId);

        Linkage::where('provincial_director_id', $director->id)
            ->where('year', $year)
            ->where('id', $linkageId)
            ->delete();

        $this->syncAccomplishedCount($director->id, $year);

        return back()->with('success', 'Linkage removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'partner_organization' => ['required', 'string', 'max:255'],
            'title'                => ['required', 'string', 'max:255'],
            'type'                 => ['required', 'in:MOA,MOU'],
            'date_signed'          => ['required', 'date'],
            'signatories'          => ['nullable', 'string', 'max:1000'],
            'remarks'              => ['nullable', 'string', 'max:1000'],
        ]);
    }

    // Provincial admins can only manage linkages for their own province's director;
    // super admin can manage any director. Role is enforced by route middleware.
    private function authorizeDirector(int $directorId): User
    {
        $user     = Auth::user();
        $director = User::where('id', $directorId)
            ->where('role', 'provincial_director')
            ->with('profile')
            ->firstOrFail();

        if ($user->role === 'provincial_admin' && $director->province_id !== $user->province_id) {
            abort(403);
        }

        return $director;
    }

    // Mirror the live record count into provincial_director_kpis.accomplished
    // so the scoring pipeline (RankingService etc.) does not need to change.
    private function syncAccomplishedCount(int $directorId, int $year): void
    {
        $kpi = KPI::where('code', 'func_linkages_established')->first();
        if (!$kpi) return;

        $count = Linkage::where('provincial_director_id', $directorId)
            ->where('year', $year)
            ->count();

        $existing = ProvincialDirectorKPI::where('provincial_director_id', $directorId)
            ->where('kpi_id', $kpi->id)
            ->where('year', $year)
            ->first();

        if ($count === 0) {
            if ($existing && ($existing->target === null || $existing->target === '')) {
                $existing->delete();
            } elseif ($existing) {
                $existing->update(['accomplished' => null]);
            }
            return;
        }

        ProvincialDirectorKPI::updateOrCreate(
            [
                'provincial_director_id' => $directorId,
                'kpi_id'                 => $kpi->id,
                'year'                   => $year,
            ],
            ['accomplished' => (string) $count]
        );
    }

    private function returnUrlFor(int $directorId, int $year): string
    {
        $user = Auth::user();
        if ($user->role === 'provincial_admin') {
            return "/provincial-kpi/{$year}";
        }
        // super admin returns to the province-keyed edit screen
        $director = User::find($directorId);
        if ($director && $director->province_id) {
            $encryptedProvince = \Illuminate\Support\Facades\Crypt::encrypt($director->province_id);
            return "/kpi-data/{$encryptedProvince}/{$year}";
        }
        return '/kpi-data';
    }
}
