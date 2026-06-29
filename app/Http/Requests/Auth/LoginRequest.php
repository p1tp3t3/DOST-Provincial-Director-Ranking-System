<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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

    // Per security directive: failed login (bad credentials, missing fields, rate
    // limit lockout) must look indistinguishable from a page reload — no error
    // messages, no flash text, no status indicator. Returns true on success.
    public function authenticate(): bool
    {
        if ($this->isRateLimited()) return false;

        $identifier = trim($this->string('identifier'));
        $password   = $this->string('password');

        $adminRoles = ['super_admin', 'sub_admin', 'provincial_admin'];

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (!$user) {
            /** @var User|null $candidate */
            $candidate = User::query()->where('dost_employee_id', $identifier)->first();
            if ($candidate && !in_array($candidate->role, $adminRoles)) {
                $user = $candidate;
            }
        }

        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());
            return false;
        }

        // Block deactivated accounts — same silent treatment as wrong credentials
        if (!$user->activate) {
            RateLimiter::hit($this->throttleKey());
            return false;
        }

        Auth::login($user, $this->boolean('remember'));
        RateLimiter::clear($this->throttleKey());
        return true;
    }

    public function isRateLimited(): bool
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return false;
        }
        event(new Lockout($this));
        return true;
    }

    // Override the default validation failure response so that missing
    // identifier / password fields also produce a silent reload instead of
    // the usual flashed error bag.
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(redirect()->back());
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('identifier')) . '|' . $this->ip());
    }
}
