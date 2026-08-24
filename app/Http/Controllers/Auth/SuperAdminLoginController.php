<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Super admin login during maintenance mode: GET/POST /admin/login/{password}.
 *
 * The {password} segment is a shared secret (config('breakglass.password_hash'),
 * managed from Super Admin > Maintenance) that gates access to the login form
 * itself — it is not a login credential. A visitor who doesn't know it gets a
 * plain 404, identical to an unknown route, so the form's existence isn't
 * revealed. Once past that gate, the actual super admin still authenticates
 * with their real account email/username + password, same as normal login.
 */
class SuperAdminLoginController extends Controller
{
    public function show(string $password)
    {
        $this->authorizeGate($password);

        if (Auth::check() && Auth::user()->role === 'super_admin') {
            return redirect('/dashboard');
        }

        return inertia('Auth/SuperAdminLogin', ['password' => $password]);
    }

    public function store(Request $request, string $password)
    {
        $this->authorizeGate($password);

        $v = Validator::make($request->all(), [
            'identifier' => 'required|string|max:255',
            'password'   => 'required|string',
        ]);
        // Silent reload on any failure (missing fields, bad creds, wrong role) —
        // no flash message, no error bag. Matches the regular login behaviour.
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
        ActivityLogHelper::login();

        return redirect('/dashboard');
    }

    /**
     * Both the gate password and maintenance mode must hold, or this 404s —
     * indistinguishable from a route that doesn't exist.
     */
    private function authorizeGate(string $password): void
    {
        if (!Cache::get('app_maintenance_mode', false)) {
            abort(404);
        }

        $hash = config('breakglass.password_hash');

        if (!$hash || !Hash::check($password, $hash)) {
            abort(404);
        }
    }
}
