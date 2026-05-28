<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ProvincialMembersSendingRequest extends FormRequest
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
        if(!$this->already_read_guidelines)
            throw ValidationException::withMessages([
                'message' => 'you must read the guidelines before sending it to the admin'
            ]);
            
        return [
            'employee_list_csv_file' => 'nullable|file|mimes:csv',
            'provincial_director_details' => 'nullable|json',
            'description' => 'nullable|string',
        ];
    }
}
