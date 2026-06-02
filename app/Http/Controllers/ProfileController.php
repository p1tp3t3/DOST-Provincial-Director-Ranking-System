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

    public function get_profile_picture(Request $request) {
        $request->validate([
            'filename' => 'required|string',
        ]);
        
        // Extract only the base name to strip out any directory paths or slashes
        $filename = basename($request->query('filename'));

        $path = storage_path('app/public/profile-pictures/' . $filename);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }

}
