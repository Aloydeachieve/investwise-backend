<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePlanRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'min_deposit' => 'required|numeric|min:0',
            'max_deposit' => 'required|numeric|min:0|gte:min_deposit',
            'profit_rate' => 'required|numeric|min:0|max:100',
            'duration_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ];
    }
}
