<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UserRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = auth()->user()?->role;

        return in_array($role, ['super_admin', 'provincial_admin']);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $actorRole  = auth()->user()?->role;
        $allowedRoles = $actorRole === 'provincial_admin'
            ? 'employee,provincial_director'
            : 'employee,provincial_director,sub_admin,super_admin';

        $manual = [
            'registration_type'    => 'required|in:manual',
            'role'                 => "required|in:{$allowedRoles}",
            'dost_employee_id'     => 'required|unique:users,dost_employee_id',
            'prefix'               => 'required|string',
            'first_name'           => 'required|string',
            'middle_name'          => 'required|string',
            'last_name'            => 'required|string',
            'suffix'               => 'nullable|string',
            'username'             => 'required|unique:users,username',
            'email'                => 'required|email|unique:users,email',
            'password'             => 'required|confirmed|min:8',
            'password_confirmation' => 'required',
        ];

        if ($this->role === 'employee') {
            $manual['position'] = 'required|string';
            $manual['status']   = 'required|in:cos,permanent,jo';
        }

        return $manual;
    }
}
