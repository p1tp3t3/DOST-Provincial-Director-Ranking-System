<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserProfileResource;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index($id) {
        $user = User::with(['profile.employeeProfile', 'province'])->find($id);

        if (!$user) abort(404);

        return inertia('Other/Profile/Main', [
            'user_profile' => new UserProfileResource($user),
        ]);
    }
    public function director_kpi_index() {

    }
    public function update_profile(Request $request) {
        
    }
    public function update_director_kpi(Request $request) {

    }
}
