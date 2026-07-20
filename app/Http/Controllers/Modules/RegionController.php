<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class RegionController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $scoped = in_array($user->role, ['regional_admin', 'regional_director']);

        $query = Region::with([
            'provinces' => fn ($q) => $q
                ->with(['directorAssignments.profile', 'users'])
                ->orderBy('name'),
        ])->orderBy('name');

        if ($scoped) {
            $query->where('id', $user->region_id);
        }

        $regions = $query->get();

        // Load regional directors keyed by region_id
        $regionalDirs = User::with('profile')
            ->whereIn('region_id', $regions->pluck('id'))
            ->where('role', 'regional_director')
            ->get()
            ->groupBy('region_id');

        $mapped = $regions->map(fn ($r) => [
            'id'                => $r->id,
            'name'              => $r->name,
            'island_under'      => $r->island_under,
            'regional_director' => $this->directorName($regionalDirs->get($r->id)?->first()),
            'provinces'         => $r->provinces->map(fn ($p) => [
                'id'                  => Crypt::encrypt($p->id),
                'name'                => $p->name,
                'category'            => $p->category,
                'staff_count'         => $p->users->where('role', 'employee')->count(),
                'provincial_director' => $this->directorName($p->provincialDirector),
            ]),
        ]);

        return inertia('Shared/Regions/Main', [
            'regions' => $mapped,
            'scoped'  => $scoped,
        ]);
    }

    private function directorName(?User $director): ?string
    {
        if (! $director || ! $director->profile) return null;
        $p      = $director->profile;
        $middle = $p->middle_name ? " {$p->middle_name}" : '';
        return "{$p->first_name}{$middle} {$p->last_name}";
    }
}
