<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role'                 => 'required|in:super_admin,sub_admin',
            'username'             => 'required|unique:users,username',
            'email'                => 'required|email|unique:users,email',
            'password'             => 'required|confirmed|min:8',
            'password_confirmation' => 'required',
        ];
    }
}
