<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Enable2FARequest extends FormRequest
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
            'verification_code' => 'required|string|size:6|regex:/^[0-9]+$/',
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
            'verification_code.size' => 'The verification code must be exactly 6 digits.',
            'verification_code.regex' => 'The verification code must contain only numbers.',
        ];
    }
}
