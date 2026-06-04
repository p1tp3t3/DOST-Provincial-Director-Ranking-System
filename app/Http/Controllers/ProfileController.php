<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserProfileResource;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // ── View another user's profile ───────────────────────────────
    public function index(int $id)
    {
        $user = User::with(['profile.employeeProfile', 'province'])->find($id);
        if (!$user) abort(404);

        return inertia('Other/Profile/Main', [
            'user_profile' => new UserProfileResource($user),
        ]);
    }

    // ── Edit own profile ──────────────────────────────────────────
    public function edit()
    {
        $user    = Auth::user()->load(['profile.employeeProfile', 'province']);
        $profile = $user->profile;

        $eduRaw  = $profile?->education_attainment;
        $eduData = is_array($eduRaw) ? ($eduRaw['data'] ?? []) : [];
        while (count($eduData) < 3) $eduData[] = '';

        return inertia('Other/Profile/Edit', [
            'role'              => $user->role,
            'username'          => $user->username,
            'email'             => $user->email,
            'province'          => $user->province?->name,
            'profile_picture'   => $profile?->profile_picture,
            'prefix'            => $profile?->prefix             ?? '',
            'first_name'        => $profile?->first_name         ?? '',
            'middle_name'       => $profile?->middle_name        ?? '',
            'last_name'         => $profile?->last_name          ?? '',
            'suffix'            => $profile?->suffix             ?? '',
            'length_of_service' => $profile?->length_of_service  ?? '',
            'education'         => array_slice($eduData, 0, 3),
            'position'          => $profile?->employeeProfile?->position ?? '',
            'status'            => $profile?->employeeProfile?->status   ?? '',
        ]);
    }

    // ── Save profile fields ───────────────────────────────────────
    public function update(Request $request)
    {
        $user    = Auth::user()->load(['profile.employeeProfile']);
        $profile = $this->getOrCreateProfile($user);

        $adminRoles      = ['super_admin', 'sub_admin', 'provincial_admin', 'provincial_sub_admin'];
        $restrictedRoles = ['employee', 'provincial_director'];

        if (in_array($user->role, $restrictedRoles)) {
            // Employee / Director: education attainment only
            $data = $request->validate([
                'education'   => 'nullable|array|max:3',
                'education.*' => 'nullable|string|max:300',
            ]);

            $profile->update([
                'education_attainment' => ['data' => array_values($data['education'] ?? [])],
            ]);

        } elseif (in_array($user->role, $adminRoles)) {
            // Admins: name fields only (no education, no employment)
            $data = $request->validate([
                'prefix'            => 'nullable|string|max:20',
                'first_name'        => 'required|string|max:100',
                'middle_name'       => 'nullable|string|max:100',
                'last_name'         => 'required|string|max:100',
                'suffix'            => 'nullable|string|max:20',
                'length_of_service' => 'nullable|string|max:20',
            ]);

            $profile->update([
                'prefix'            => $data['prefix']            ?? null,
                'first_name'        => $data['first_name'],
                'middle_name'       => $data['middle_name']       ?? '',
                'last_name'         => $data['last_name'],
                'suffix'            => $data['suffix']            ?? null,
                'length_of_service' => $data['length_of_service'] ?? '',
            ]);
        }

        return back()->with('success', 'Profile updated successfully.');
    }

    // ── Upload cropped profile picture ────────────────────────────
    public function update_picture(Request $request)
    {
        $request->validate([
            'picture' => 'required|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        $user    = Auth::user()->load('profile');
        $profile = $this->getOrCreateProfile($user);

        // Delete previous file if it exists
        $old = $profile->profile_picture;
        if ($old && Storage::disk('local')->exists('profile-pictures/' . $old)) {
            Storage::disk('local')->delete('profile-pictures/' . $old);
        }

        $filename = 'profile-' . now()->format('Y-m-d-His') . '-' . $user->id . '.jpg';
        Storage::disk('local')->putFileAs('profile-pictures', $request->file('picture'), $filename);

        $profile->update(['profile_picture' => $filename]);

        return back()->with('picture_updated', true);
    }

    // ── Get or create profile row ─────────────────────────────────
    private function getOrCreateProfile(User $user): Profile
    {
        if ($user->profile) {
            return $user->profile;
        }

        $profile = Profile::create([
            'user_id'              => $user->id,
            'first_name'           => '',
            'middle_name'          => '',
            'last_name'            => '',
            'length_of_service'    => '',
            'education_attainment' => ['data' => []],
        ]);

        // Keep the relation in sync so subsequent $user->profile calls work
        $user->setRelation('profile', $profile);

        return $profile;
    }

    // ── Serve profile picture file ────────────────────────────────
    public function get_profile_picture(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
        ]);

        $filename = basename($request->query('filename'));
        $path     = Storage::disk('local')->path('profile-pictures/' . $filename);

        if (!file_exists($path)) {
            $path = public_path('assets/default-profile.avif');
        };

        return response()->file($path);
    }

    public function director_kpi_index() {}
    public function update_director_kpi() {}
    public function destroy() {}
}
