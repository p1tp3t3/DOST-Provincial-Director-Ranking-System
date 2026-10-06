<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Lets a user who holds 2+ locked roles pick which one is currently "active"
// — the hat that drives the sidebar/dashboard/permissions for the rest of
// their session, exactly like the old single-role column used to.
class RoleSwitchController extends Controller
{
    public function edit()
    {
        return inertia('Auth/ChooseRole', [
            'roles' => Auth::user()->lockedRoles()->map(fn ($r) => [
                'id'   => $r->id,
                'name' => $r->name,
            ])->values(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate(['role' => ['required', 'string']]);

        $user = Auth::user();
        $role = $user->lockedRoles()->firstWhere('name', $request->role);

        if (!$role) {
            return back()->withErrors(['role' => 'You do not currently hold that role.']);
        }

        $user->forceFill(['active_role_id' => $role->id])->save();

        return redirect()->route('dashboard');
    }
}
