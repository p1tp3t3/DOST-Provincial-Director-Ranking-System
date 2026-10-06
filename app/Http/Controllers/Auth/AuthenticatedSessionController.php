<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Helpers\ActivityLogHelper;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Silent reload on failed credentials — no flash message, no error bag.
        if (!$request->authenticate()) {
            return redirect()->back();
        }

        $request->session()->regenerate();
        ActivityLogHelper::login();

        $user   = Auth::user();
        $locked = $user->lockedRoles();

        if ($locked->isEmpty()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'identifier' => 'Your account has no active role. Contact an administrator.',
            ]);
        }

        if ($locked->count() === 1) {
            if ($user->active_role_id !== $locked->first()->id) {
                $user->forceFill(['active_role_id' => $locked->first()->id])->save();
            }
        } elseif (!$locked->contains('id', $user->active_role_id)) {
            return redirect()->route('role.choose');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        // Log while the user is still authenticated
        ActivityLogHelper::logout();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Inertia::location forces a full browser redirect, which clears
        // the stale CSRF token that would cause a 419 on the next request.
        return Inertia::location(route('login'));
    }
}
