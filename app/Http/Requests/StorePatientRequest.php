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
            'phone'       => 'required|string|unique:patients,phone',
            'med_history' => 'nullable|string',
            'images'      => 'required|array',
            'images.*'    => 'image|mimes:jpeg,png,jpg|max:5120',
        ];
    }
}
