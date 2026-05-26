<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserProfileResource;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index($id) {
        $data = User::has('profile')
                    ->with(['profile.employeeProfile', 'province'])
                    ->where('dost_employee_id', $id)
                    ->get();

        return inertia('Other/Profile/Main', [
            'user_profile' => UserProfileResource::collection($data)[0]
        ]);
    }
    public function director_kpi_index() {

    }
    public function update_profile(Request $request) {
        
    }
    public function update_director_kpi(Request $request) {

    }
}
