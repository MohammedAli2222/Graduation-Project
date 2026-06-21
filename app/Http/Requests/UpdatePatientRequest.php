<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
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
        $patientId = $this->route('id');

        return [
            'full_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|unique:patients,phone,'.$patientId,

            'preliminary_diagnosis' => 'nullable|string',

            'has_general_diseases' => 'sometimes|boolean',
            'general_diseases_details' => 'required_if:has_general_diseases,1|nullable|string',

            'is_special_needs' => 'sometimes|boolean',
            'special_needs_details' => 'required_if:is_special_needs,1|nullable|string',

            'takes_medications' => 'sometimes|boolean',
            'medications_details' => 'required_if:takes_medications,1|nullable|string',

            'has_allergies' => 'sometimes|boolean',
            'allergies_details' => 'required_if:has_allergies,1|nullable|string',

            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ];
    }
}
