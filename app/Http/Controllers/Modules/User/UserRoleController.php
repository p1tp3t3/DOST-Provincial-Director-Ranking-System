<?php

namespace App\Http\Controllers\Modules\User;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

// Manages a single user's role assignments (add / lock-unlock / remove).
// Separate from UserController::update(), which only ever touches account
// and profile fields now — a user can hold several roles, so there's no
// single "role" value for that endpoint to set anymore.
class UserRoleController extends Controller
{
    public function store(Request $request, int $id)
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::findOrFail($id);

        try {
            $user->assignRole($data['role']);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return back()->with('success', 'Role added.');
    }

    public function update(Request $request, int $id, int $roleId)
    {
        $data = $request->validate([
            'locked' => ['required', 'boolean'],
        ]);

        $user = User::findOrFail($id);
        $role = Role::findOrFail($roleId);

        if ($data['locked'] && $user->activate) {
            try {
                $user->assertDirectorSlotFree($role->name);
            } catch (ValidationException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        $user->roles()->updateExistingPivot($role->id, ['locked' => $data['locked']]);

        // If we just unlocked the role the user is currently "wearing," they
        // can't stay on it — auto-promote their other locked role if exactly
        // one remains, otherwise clear it so they're asked to pick on next login.
        if (!$data['locked'] && $user->active_role_id === $role->id) {
            $stillLocked = $user->fresh()->lockedRoles();
            $user->forceFill([
                'active_role_id' => $stillLocked->count() === 1 ? $stillLocked->first()->id : null,
            ])->save();
        }

        return back()->with('success', 'Role updated.');
    }

    public function destroy(int $id, int $roleId)
    {
        $user = User::findOrFail($id);
        $role = Role::findOrFail($roleId);

        try {
            $user->removeRole($role->name);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return back()->with('success', 'Role removed.');
    }
}
