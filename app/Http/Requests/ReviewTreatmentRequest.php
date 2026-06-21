<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'treatment_id' => 'required|exists:treatments,id',
            'action' => 'required|in:approve,reject',
            'instructor_notes' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'treatment_id.required' => 'The treatment case ID  required.',
            'treatment_id.exists' => 'The selected treatment record does not exist in our database.',

            'action.required' => 'The review action (approve or reject) must be specified.',
            'action.in' => 'The review action must be either "approve" or "reject" only.',

            'instructor_notes.required' => 'Instructor notes are strictly required for both approval and rejection cases.',
            'instructor_notes.string' => 'The instructor notes field must be a valid text string.',
            'instructor_notes.max' => 'The instructor notes field must not exceed 1000 characters.',
        ];
    }
}
