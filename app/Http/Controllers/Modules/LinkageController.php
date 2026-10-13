<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Linkage;
use App\Models\User;
use App\Services\KpiEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

// Evidence-based KPI module backing the `func_linkages_established` KPI.
// Adding/removing rows here is the only way to change the accomplishment count.
// Records belong to the province; each quarter's count comes from the records'
// signing dates and is rolled up into the annual figure (KpiEntryService), so
// a record dated in a locked quarter can't be changed.
class LinkageController extends Controller
{
    private const KPI_CODE = 'func_linkages_established';

    public function __construct(private KpiEntryService $entries) {}

    public function index(int $directorId, int $year)
    {
        $director = $this->authorizeDirector($directorId);

        $linkages = Linkage::where('province_id', $director->province_id)
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

        return inertia('Modules/Linkages/Index', [
            'director'   => ['id' => $director->id, 'name' => $directorName ?: '—'],
            'year'       => $year,
            'linkages'   => $linkages,
            'count'      => $linkages->count(),
            'return_url' => $this->returnUrlFor($director, $year),
        ]);
    }

    public function store(Request $request, int $directorId, int $year)
    {
        $director = $this->authorizeDirector($directorId);
        $data     = $this->validated($request);
        $this->entries->assertRecordQuarterOpen($director->province_id, $year, $data['date_signed']);

        Linkage::create([
            ...$data,
            'province_id'            => $director->province_id,
            'provincial_director_id' => $director->id,
            'year'                   => $year,
        ]);

        $this->entries->syncRecordKpi(self::KPI_CODE, $director->province_id, $year, Auth::id());

        return back()->with('success', 'Linkage added.');
    }

    public function update(Request $request, int $directorId, int $year, int $linkageId)
    {
        $director = $this->authorizeDirector($directorId);
        $linkage  = Linkage::where('province_id', $director->province_id)
            ->where('year', $year)
            ->findOrFail($linkageId);

        $data = $this->validated($request);
        $this->entries->assertRecordQuarterOpen($director->province_id, $year, $linkage->date_signed);
        $this->entries->assertRecordQuarterOpen($director->province_id, $year, $data['date_signed']);

        $linkage->update($data);

        // A new signing date can move the record to another quarter.
        $this->entries->syncRecordKpi(self::KPI_CODE, $director->province_id, $year, Auth::id());

        return back()->with('success', 'Linkage updated.');
    }

    public function destroy(int $directorId, int $year, int $linkageId)
    {
        $director = $this->authorizeDirector($directorId);
        $linkage  = Linkage::where('province_id', $director->province_id)
            ->where('year', $year)
            ->findOrFail($linkageId);

        $this->entries->assertRecordQuarterOpen($director->province_id, $year, $linkage->date_signed);
        $linkage->delete();

        $this->entries->syncRecordKpi(self::KPI_CODE, $director->province_id, $year, Auth::id());

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
            ->whereRole('provincial_director')
            ->with('profile')
            ->firstOrFail();

        if ($user->role === 'provincial_admin' && $director->province_id !== $user->province_id) {
            abort(403);
        }

        return $director;
    }

    private function returnUrlFor(User $director, int $year): string
    {
        if (Auth::user()->role === 'provincial_admin') {
            return "/provincial-kpi/{$year}";
        }
        // super admin returns to the province-keyed edit screen
        return $director->province_id
            ? '/kpi-data/' . Crypt::encrypt($director->province_id) . "/{$year}"
            : '/kpi-data';
    }
}
