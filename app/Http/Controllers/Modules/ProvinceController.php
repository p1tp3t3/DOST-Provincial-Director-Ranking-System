<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProvinceProfileResource;
use App\Http\Resources\ProvinceResource;
use App\Models\Province;

class ProvinceController extends Controller
{
    public function index() {
        $data = Province::with(['provincialDirector.profile', 'user'])->get();

        return inertia('Other/Province/Main', [
            'provinces' => ProvinceResource::collection($data)
        ]);
    }

    public function province_profile_index($id) {
        $data = Province::with(['provincialDirector.profile', 'user'])->find($id);

        return inertia('Other/Province/Main', [
            'province_profile' => ProvinceProfileResource::collection($data)
        ]);
    }
}
