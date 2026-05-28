<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProvinceCreationRequest;
use App\Http\Resources\ProvinceProfileResource;
use App\Http\Resources\ProvinceResource;
use App\Models\Province;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ProvinceController extends Controller
{
    public function index() {
        $data = Province::with(['provincialDirector.profile', 'user'])->get();
        return inertia("Other/Province/Main", [
            'provinces' => ProvinceResource::collection($data)
        ]);
    }

    public function province_profile_index($id) {
        $decripted_id = Crypt::decrypt($id);
        $data = Province::with(['provincialDirector.profile', 'user.profile.employeeProfile'])
                        ->has('user.profile.employeeProfile')
                        ->whereHas('user', function($q) {
                            $q->where('role', 'employee')
                              ->latest('created_at');
                        })
                        ->where('id', $decripted_id)
                        ->get();

        return inertia('Other/Province/Profile', [
            'province_profile' => ProvinceProfileResource::collection($data)
        ]);
    }

    public function store(ProvinceCreationRequest $request) {
        $data = $request->validated();
        $province = Province::create([
                        'name' => $data['name'],
                        'category' => $data['category']
                    ]);
        User::create([
            'role' => 'provincial_admin',
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password']
        ]);
        //insert provincial admin to db
        //return success
    }
}
