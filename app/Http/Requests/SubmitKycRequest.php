<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->status === 'active';
    }

    public function rules(): array
    {
        $rules = [
            'documents' => 'required|array|min:1|max:5',
            'documents.*.type' => 'required|in:passport,driver_license,voters_card,nin,bvn',
            'nin' => 'nullable|string|size:11',
            'dob' => 'nullable|date|before:today',
            'bvn' => 'nullable|string|size:11',
        ];

        // Add file validation rules dynamically based on document types
        $documents = $this->input('documents', []);
        foreach ($documents as $index => $document) {
            $type = $document['type'] ?? '';

            // For BVN, file is not required
            if ($type === 'bvn') {
                continue;
            }

            // For NIN, file is optional but if provided, must be valid
            if ($type === 'nin') {
                $rules["documents.$index.file"] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120';
            } else {
                // For other document types (passport, driver's license, voter's card), file is required
                $rules["documents.$index.file"] = 'required|file|mimes:jpg,jpeg,png,pdf|max:5120';
            }
        }

        return $rules;
    }

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

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $types = collect($this->input('documents', []))
                ->pluck('type')
                ->toArray();

            if (in_array('nin', $types)) {
                if (!$this->filled('nin')) {
                    $validator->errors()->add('nin', 'NIN is required when uploading NIN document.');
                }
                if (!$this->filled('dob')) {
                    $validator->errors()->add('dob', 'Date of Birth is required when uploading NIN document.');
                }
            }

            if (in_array('bvn', $types)) {
                if (!$this->filled('bvn')) {
                    $validator->errors()->add('bvn', 'BVN is required when uploading BVN document.');
                }
            }
        });
    }
}
