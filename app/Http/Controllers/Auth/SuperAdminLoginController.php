<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

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

        $request->validate([
            'identifier' => 'required|string|max:255',
            'password'   => 'required|string',
        ]);

        $id = trim($request->identifier);

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $id)
            ->orWhere('username', $id)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['identifier' => 'Invalid credentials.']);
        }

        if ($user->role !== 'super_admin') {
            return back()->withErrors(['identifier' => 'Access restricted to super administrators only.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect('/dashboard');
    }
}
