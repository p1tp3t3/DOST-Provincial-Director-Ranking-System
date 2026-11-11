<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProvinceCreationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'name'                   => ['required', 'string', 'max:255', 'unique:provinces,name'],
            'category'               => ['required', 'in:micro,small,medium,large'],
            'admin_employee_id'      => ['required', 'string', 'max:50', 'unique:users,dost_employee_id'],
            'admin_email'            => ['required', 'email', 'unique:users,email'],
            'admin_password'         => ['required', 'string', 'min:8'],
            'confirm_admin_password' => ['required', 'same:admin_password'],
        ];
    }
}
