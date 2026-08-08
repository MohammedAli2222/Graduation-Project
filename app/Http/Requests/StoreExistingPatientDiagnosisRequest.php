<?php

namespace App\Http\Requests;

use App\Enums\DiagnosisStatus;
use App\Models\PatientDiagnose;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class StoreExistingPatientDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'case_type_ids' => 'required|array|min:1|max:5',
            'case_type_ids.*' => 'required|exists:case_types,id',

            'estimated_costs' => 'required|array',
            'estimated_costs.*' => 'required|numeric|min:0',

            'clinical_images' => 'required|array',
            'clinical_images.*' => 'nullable',
            'clinical_images.*.*' => 'image|mimes:jpeg,png,jpg|max:5120',

            'x_ray_images' => 'required|array',
            'x_ray_images.*' => 'nullable',
            'x_ray_images.*.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ];
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

        throw new HttpResponseException(
            response_error(null, 422, $errorMessage)
        );
    }
}
