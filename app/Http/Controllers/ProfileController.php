<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserProfileResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'cover_picture'     => $profile?->cover_picture,
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
        $user = Auth::user()->load(['profile.employeeProfile']);

        if (!$user->profile) {
            return back()->withErrors(['profile' => 'No profile found for this account.']);
        }

        $adminRoles      = ['super_admin', 'sub_admin', 'provincial_admin', 'provincial_sub_admin'];
        $restrictedRoles = ['employee', 'provincial_director'];

        if (in_array($user->role, $restrictedRoles)) {
            // Employee / Director: education attainment only
            $data = $request->validate([
                'education'   => 'nullable|array|max:3',
                'education.*' => 'nullable|string|max:300',
            ]);

            $user->profile->update([
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

            $user->profile->update([
                'prefix'            => $data['prefix']            ?? null,
                'first_name'        => $data['first_name'],
                'middle_name'       => $data['middle_name']       ?? null,
                'last_name'         => $data['last_name'],
                'suffix'            => $data['suffix']            ?? null,
                'length_of_service' => $data['length_of_service'] ?? null,
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

        $user = Auth::user()->load('profile');

        if (!$user->profile) {
            return back()->withErrors(['picture' => 'No profile found for this account.']);
        }

        // Delete previous file if it exists
        $old = $user->profile->profile_picture;
        if ($old) {
            $oldPath = storage_path('app/public/profile-pictures/' . $old);
            if (file_exists($oldPath)) @unlink($oldPath);
        }

        $filename = 'profile-' . now()->format('Y-m-d-His') . '-' . $user->id . '.jpg';
        $request->file('picture')->storeAs('public/profile-pictures', $filename);

        $user->profile->update(['profile_picture' => $filename]);

        return back()->with('picture_updated', true);
    }

    // ── Upload cropped cover picture ──────────────────────────────
    public function update_cover_picture(Request $request)
    {
        $request->validate([
            'cover' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $user = Auth::user()->load('profile');

        if (!$user->profile) {
            return back()->withErrors(['cover' => 'No profile found for this account.']);
        }

        $old = $user->profile->cover_picture;
        if ($old) {
            $oldPath = storage_path('app/public/profile-pictures/' . $old);
            if (file_exists($oldPath)) @unlink($oldPath);
        }

        $filename = 'cover-' . now()->format('Y-m-d-His') . '-' . $user->id . '.jpg';
        $request->file('cover')->storeAs('public/profile-pictures', $filename);

        $user->profile->update(['cover_picture' => $filename]);

        return back()->with('cover_updated', true);
    }

    // ── Serve profile picture file ────────────────────────────────
    public function get_profile_picture(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
        ]);

        $filename = basename($request->query('filename'));
        $path     = storage_path('app/public/profile-pictures/' . $filename);

        if (!file_exists($path)) abort(404);

        return response()->file($path);
    }

    public function director_kpi_index() {}
    public function update_director_kpi() {}
    public function destroy() {}
}
