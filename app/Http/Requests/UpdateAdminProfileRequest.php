<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'display_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|regex:/^[\+]?[0-9\-\s\(\)]+$/',
            'telegram' => 'nullable|string|max:255|regex:/^@?[a-zA-Z0-9_]+$/',
            'dob' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number format is invalid.',
            'telegram.regex' => 'The telegram username format is invalid.',
            'dob.before' => 'The date of birth must be before today.',
        ];
    }
}
