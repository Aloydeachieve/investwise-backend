<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->status === 'active';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_email' => 'required|email|exists:users,email',
            'new_email' => 'required|email|unique:users,email|different:current_email',
            'password' => 'required|string|min:8',
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
            'current_email.exists' => 'The current email does not exist in our records.',
            'new_email.unique' => 'The new email address is already in use.',
            'new_email.different' => 'The new email must be different from the current email.',
            'password.min' => 'The password must be at least 8 characters long.',
        ];
    }
}
