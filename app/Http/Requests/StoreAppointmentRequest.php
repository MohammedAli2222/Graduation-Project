<?php

namespace App\Http\Requests;

use App\Models\PatientDiagnose;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'diagnosis_id' => 'required|integer|exists:patient_diagnoses,id',
            'appointment_date' => 'required|date|date_format:Y-m-d|after_or_equal:2026-07-03',
            'slot_number' => [
                'required',
                'integer',
                'min:1',
                'max:4',
                function ($attribute, $value, $fail) {
                    $diagnosis = PatientDiagnose::find($this->input('diagnosis_id'));
                    if ($diagnosis) {
                        $slotsNeeded = (int) $diagnosis->caseType->slots_needed;
                        if (($value + $slotsNeeded - 1) > 4) {
                            $fail('The selected starting slot, combined with the case requirements, exceeds university working hours.');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'diagnosis_id.required' => 'The diagnosis reference ID is required.',
            'diagnosis_id.exists' => 'The selected diagnosis does not exist in our records.',

            'appointment_date.required' => 'Please provide a valid date for the appointment.',
            'appointment_date.date_format' => 'The date format must be exactly YYYY-MM-DD.',
            'appointment_date.after_or_equal' => 'The appointment date cannot be in the past.',

            'slot_number.required' => 'You must select a specific clinic time slot.',
            'slot_number.min' => 'The slot must be at least 1.',
            'slot_number.max' => 'The slot cannot be greater than 4.',
        ];
    }
}
