<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DiagnoseRequest extends FormRequest
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
            'patient_id' => 'required|exists:patients,id',
            'diagnoses' => 'required|array|min:1',
            'diagnoses.*.case_type_id' => 'required|exists:case_types,id',
            'diagnoses.*.final_diagnosis' => 'required|string|max:5000',
            'diagnoses.*.suggested_by_student_id' => 'nullable|exists:users,id',

            'diagnoses.*.media_ids' => 'nullable|array',
            'diagnoses.*.media_ids.*' => 'exists:media,id',
        ];
    }
}
