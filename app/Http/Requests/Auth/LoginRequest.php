<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'password'   => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identifier = trim($this->string('identifier'));
        $password   = $this->string('password');

        // Admin roles may only log in via email or username
        $adminRoles = ['super_admin', 'sub_admin', 'provincial_admin', 'provincial_sub_admin'];

        // Step 1: try email or username (all roles)
        /** @var User|null $user */
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        // Step 2: if still not found, try DOST employee ID (non-admin roles only)
        if (!$user) {
            /** @var User|null $candidate */
            $candidate = User::query()->where('dost_employee_id', $identifier)->first();

            if ($candidate && !in_array($candidate->role, $adminRoles)) {
                $user = $candidate;
            }
        }

        // Step 3: verify password and login
        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'identifier' => trans('auth.failed'),
            ]);
        }

        Auth::login($user, $this->boolean('remember'));
        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'identifier' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('identifier')) . '|' . $this->ip());
    }
}
