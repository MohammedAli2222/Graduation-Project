<?php

namespace App\Http\Requests;

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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'diagnosis_id' => 'required|integer|exists:patient_diagnoses,id',
            'appointment_date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
            'slot_number' => 'required|integer|between:1,4',
        ];
    }

    public function messages(): array
    {
        return [
            'diagnosis_id.required' => 'The diagnosis reference ID is required.',
            'diagnosis_id.exists' => 'The selected diagnosis does not exist in our records.',

            'appointment_date.required' => 'Please provide a valid date for the appointment.',
            'appointment_date.date_format' => 'The date format must be exactly YYYY-MM-DD (e.g., 2026-06-15).',
            'appointment_date.after_or_equal' => 'The appointment date cannot be in the past.',

            'slot_number.required' => 'You must select a specific clinic time slot.',
            'slot_number.between' => 'The selected slot is invalid. Please select a valid period between 1 and 4.',
        ];
    }
}
