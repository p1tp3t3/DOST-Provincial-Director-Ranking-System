<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['identifier' => 'required|string|max:255']);

        $id   = trim($request->identifier);
        $user = User::query()
            ->where('email', $id)
            ->orWhere('username', $id)
            ->orWhere('dost_employee_id', $id)
            ->first();

        // Send link only when a user is found; always respond the same way
        // to prevent account enumeration.
        if ($user) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('status', trans(Password::RESET_LINK_SENT));
    }
}
