<?php

namespace App\Http\Requests\User;

use App\Models\Province;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

        // A provincial admin can only ever register into their own province, so
        // the form never shows a province picker for them — the controller fills
        // it in from the actor's own assignment instead. Only super_admin/sub_admin
        // (who manage multiple provinces) actually submit this field.
        $manual = [
            'registration_type'    => 'required|in:manual',
            'role'                 => "required|in:{$allowedRoles}",
            'province'             => $actorRole === 'provincial_admin' ? 'nullable|exists:provinces,id' : 'required|exists:provinces,id',
            'dost_employee_id'     => 'required|unique:users,dost_employee_id',
            'prefix'               => 'required|string',
            'first_name'           => 'required|string',
            'middle_name'          => 'required|string',
            'last_name'            => 'required|string',
            'suffix'               => 'nullable|string',
            'length_of_service'    => 'nullable|string',
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

    // A province should only have one active director at a time. Block
    // registering a new provincial_director while the existing one (if any)
    // is still activated — the actor must deactivate them first.
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('role') !== 'provincial_director') {
                return;
            }

            $actor      = auth()->user();
            $provinceId = $actor->role === 'provincial_admin' ? $actor->province_id : $this->input('province');
            if (!$provinceId) {
                return;
            }

            $hasActiveDirector = Province::find($provinceId)
                ?->directorAssignments()
                ->where('activate', true)
                ->exists();

            if ($hasActiveDirector) {
                $validator->errors()->add(
                    'role',
                    'This province already has an active provincial director. Deactivate them before registering a new one.'
                );
            }
        });
    }
}
