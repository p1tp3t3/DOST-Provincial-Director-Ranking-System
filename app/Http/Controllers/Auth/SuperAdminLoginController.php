<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SuperAdminLoginController extends Controller
{
    public function show()
    {
        if (!Cache::get('app_maintenance_mode', false)) {
            return redirect()->route('login');
        }

        if (Auth::check() && Auth::user()->role === 'super_admin') {
            return redirect('/dashboard');
        }

        return inertia('Auth/SuperAdminLogin');
    }

    public function store(Request $request)
    {
        if (!Cache::get('app_maintenance_mode', false)) {
            return redirect()->route('login');
        }

        // Silent reload on any failure (missing fields, bad creds, wrong role) — no
        // flash message, no error bag. Matches the regular login behaviour.
        $v = Validator::make($request->all(), [
            'identifier' => 'required|string|max:255',
            'password'   => 'required|string',
        ]);
        if ($v->fails()) return back();

        $id = trim($request->identifier);

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $id)
            ->orWhere('username', $id)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back();
        }
        if ($user->role !== 'super_admin') {
            return back();
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect('/dashboard');
    }
}
