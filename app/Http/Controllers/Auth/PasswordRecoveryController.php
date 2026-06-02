<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordRecoveryController extends Controller
{
    public function show()
    {
        return inertia('Auth/ForgotPassword', [
            'sent' => session('recovery_sent', false),
        ]);
    }

    public function send_link(Request $request)
    {
        $request->validate(['identifier' => 'required|string|max:255']);

        $id = trim($request->identifier);

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $id)
            ->orWhere('username', $id)
            ->orWhere('dost_employee_id', $id)
            ->first();

        // Always respond with "sent" to prevent user enumeration
        if ($user) {
            $token = Str::random(64);
            Cache::put("pwd_reset_{$token}", $user->id, now()->addHour());

            $link = route('password.recovery.form', ['token' => $token]);

            Mail::raw(
                "PDRIS Password Recovery\n\n" .
                "You requested to reset your password. Click the link below:\n\n" .
                "{$link}\n\n" .
                "This link expires in 1 hour.\n" .
                "If you did not request this, please ignore this email.",
                fn($msg) => $msg->to($user->email)->subject('Password Reset Link — PDRIS')
            );
        }

        return back()->with('recovery_sent', true);
    }

    public function reset_form(string $token)
    {
        $userId = Cache::get("pwd_reset_{$token}");

        if (!$userId) {
            return inertia('Auth/ResetPassword', [
                'valid' => false,
                'token' => $token,
                'email' => '',
            ]);
        }

        /** @var User|null $user */
        $user = User::query()->where('id', $userId)->first();

        return inertia('Auth/ResetPassword', [
            'valid' => true,
            'token' => $token,
            'email' => $user ? $this->maskEmail($user->email) : '',
        ]);
    }

    public function reset_password(Request $request, string $token)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $userId = Cache::get("pwd_reset_{$token}");

        if (!$userId) {
            return back()->withErrors(['token' => 'This link has expired or is invalid. Please request a new one.']);
        }

        /** @var User|null $user */
        $user = User::query()->where('id', $userId)->first();

        if (!$user) {
            return back()->withErrors(['token' => 'User not found. Please request a new link.']);
        }

        $user->update(['password' => Hash::make($request->password)]);
        Cache::forget("pwd_reset_{$token}");

        return redirect()->route('login')->with('status', 'Password reset successfully. You can now log in.');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = min(2, strlen($local));
        $masked  = substr($local, 0, $visible) . str_repeat('*', max(strlen($local) - $visible, 3));

        return "{$masked}@{$domain}";
    }
}
