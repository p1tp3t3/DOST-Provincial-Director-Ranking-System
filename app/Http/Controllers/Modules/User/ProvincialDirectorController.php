<?php

namespace App\Http\Controllers\Modules\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\DirectorResource;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\Request;

class ProvincialDirectorController extends Controller
{
    public function index() {
        $query = User::has('profile')
                    ->with(['provinces', 'profile'])
                    ->where('role', 'provincial_director');

        if (auth()->user()->role === 'regional_admin') {
            $region = Region::with('provinces')->findOrFail(auth()->user()->region_id);
            $query->whereProvinceIn($region->provinces->pluck('id')->toArray());
        }

        $data = $query->paginate(12);

        return inertia('Other/Director/Main', [
            'directors' => DirectorResource::collection($data)
        ]);
    }
}
