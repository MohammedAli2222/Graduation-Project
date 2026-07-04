<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFollowUpRequest extends FormRequest
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
            'treatment_id' => 'required|exists:treatments,id',
            'appointment_date' => 'required|date|date_format:Y-m-d|after:today',
            'slot_number' => 'required|integer|between:1,4',
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_date.after' => 'The follow-up date must be in the future.',
            'slot_number.between' => 'Please select a valid clinic slot (1-4).',
        ];
    }
}
