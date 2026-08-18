<?php

namespace App\Http\Requests;

use App\Models\PatientDiagnose;
use Carbon\Carbon;
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
        $slotsPerDay = (int) config('clinic.working_slots_per_day');

        return [
            'diagnosis_id' => 'required|integer|exists:patient_diagnoses,id',
            // كانت مثبّتة على تاريخ ثابت (2026-07-03) بدل اليوم الحالي، فكل تاريخ
            // بين ذلك الثابت واليوم الحالي كان يُقبل رغم كونه ماضياً فعلاً.
            'appointment_date' => 'required|date|date_format:Y-m-d|after_or_equal:'.Carbon::now('Asia/Damascus')->format('Y-m-d'),
            'slot_number' => [
                'required',
                'integer',
                'min:1',
                'max:'.$slotsPerDay,
                function ($attribute, $value, $fail) use ($slotsPerDay) {
                    $diagnosis = PatientDiagnose::find($this->input('diagnosis_id'));

                    if (! $diagnosis) {
                        return;
                    }

                    $slotsNeeded = (int) $diagnosis->caseType->slots_needed;

                    if (($value + $slotsNeeded - 1) <= $slotsPerDay) {
                        return;
                    }

                    // الرسالة العامة السابقة كانت تخفي السبب: نذكر الأرقام الفعلية
                    // حتى يعرف الطالب من أي فترة يستطيع البدء بدل التخمين
                    $maxStartSlot = $slotsPerDay - $slotsNeeded + 1;

                    if ($maxStartSlot < 1) {
                        $fail(sprintf(
                            'This case requires %d consecutive slots, which exceeds the %d slots available in a working day. It cannot be booked.',
                            $slotsNeeded,
                            $slotsPerDay
                        ));

                        return;
                    }

                    $fail(sprintf(
                        'This case requires %d consecutive slots. With %d slots per working day, the starting slot must be %d or lower.',
                        $slotsNeeded,
                        $slotsPerDay,
                        $maxStartSlot
                    ));
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
            'slot_number.max' => 'The slot cannot be greater than '.config('clinic.working_slots_per_day').'.',
        ];
    }
}
