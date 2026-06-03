<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\DiagnosisStatus;
use App\Models\PatientDiagnose;
use Illuminate\Validation\Validator;

class StudentStorePatientRequest extends StorePatientRequest
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

        $rules = parent::rules();

        $rules['preliminary_diagnosis'] = 'required|string|max:1000';

        $rules['case_type_id'] = 'required|exists:case_types,id';



        return $rules;
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $studentId = auth()->id();

            $hasPendingRequest = PatientDiagnose::where('suggested_by_student_id', $studentId)
                ->where('status', DiagnosisStatus::WAITING_APPROVAL->value)
                ->exists();

            if ($hasPendingRequest) {
                $validator->errors()->add(
                    'pending_limit',
                    'Sorry Doctor, you cannot submit a new case at this time. You already have a pending patient request awaiting review by the instructor.'
                );
            }
        });
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {

        $errorMessage = $validator->errors()->first();

        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response_error(null, 422, $errorMessage)
        );
    }
}
