<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitKycRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->is_authenticated();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'documents' => 'required|array|min:1|max:5',
            'documents.*.type' => 'required|in:passport,driver_license,national_id,proof_of_address,utility_bill,bank_statement',
            'documents.*.file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
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
            'documents.required' => 'At least one document is required.',
            'documents.min' => 'At least one document must be provided.',
            'documents.max' => 'Maximum of 5 documents can be uploaded at once.',
            'documents.*.type.required' => 'Document type is required.',
            'documents.*.type.in' => 'Invalid document type selected.',
            'documents.*.file.required' => 'Document file is required.',
            'documents.*.file.file' => 'Invalid file provided.',
            'documents.*.file.mimes' => 'Only JPG, PNG, and PDF files are allowed.',
            'documents.*.file.max' => 'Each file must not exceed 5MB in size.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Add any data preparation logic here if needed
    }
}
