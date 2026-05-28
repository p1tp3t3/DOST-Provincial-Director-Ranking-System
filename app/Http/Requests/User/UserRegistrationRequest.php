<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UserRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $role = auth()->user()->role;

        return auth()->check()
               ?
               $role == 'super_admin'
               :
               false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->registration_type;

        if(!in_array($type, ['manual', 'auto']))
            throw ValidationException::withMessages([
                'registration_type' => 'the user registration type must be manual and auto'
            ]);
        
        $fields = [
            'manual' => [
                'role' => 'required|in:employee,provincial_director,sub_admin,super_admin',
                'dost_employee_id' => "required|unique:users,dost_employee_id",
                'province' => 'nullable|exists:provinces,id',
                'prefix' => 'required',
                'first_name' => 'required',
                'middle_name' => 'required',
                'last_name' => 'required',
                'suffix' => 'required',
                'username' => "required|unique:users,username",
                'email' => 'required|email|unique:users,email',
                'password' => 'required|confirmed',
                'password_confirmation' => 'required',
            ],
            'auto' => [
                'province' => 'required|exists:provinces,id',
                'status' => 'required|in:approved,rejected',
                'role' => 'required|in:employee,provincial_director',
                'csv_file' => 'nullable|file|mimes:csv',
                'provincial_director_details' => 'nullable|json',
                'description' => 'nullable|string'
            ]
        ];

        $fields = $this->role == 'employee'
                  ?
                  array_merge($fields['manual'], [
                    'position' => 'required|string',
                    'status' => 'required|in:cos,permanent,jo',
                  ])
                  : $fields;

        return $fields[$type];
    }
}
