<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
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
        return [
            'full_name'   => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'phone'       => 'required|string|unique:patients,phone',
            'preliminary_diagnosis' => 'nullable|string|max:1000',

            'has_general_diseases'      => 'required|boolean',
            'general_diseases_details'  => 'nullable|required_if:has_general_diseases,true|string',
            'is_special_needs'          => 'required|boolean',
            'special_needs_details'     => 'nullable|required_if:is_special_needs,true|string',
            'takes_medications'         => 'required|boolean',
            'medications_details'       => 'nullable|required_if:takes_medications,true|string',
            'has_allergies'             => 'required|boolean',
            'allergies_details'         => 'nullable|required_if:has_allergies,true|string',

            'images'      => 'required|array',
            'images.*'    => 'image|mimes:jpeg,png,jpg|max:5120',
        ];
    }
}
