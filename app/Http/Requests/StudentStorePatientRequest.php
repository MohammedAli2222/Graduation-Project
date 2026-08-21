<?php

namespace App\Http\Requests;

use App\Enums\DiagnosisStatus;
use App\Models\PatientDiagnose;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Exceptions\HttpResponseException;
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

        // التشخيص الأولي لم يعد نصاً حراً: الطالب يختار نوع حالة من نفس
        // قائمة "نوع الحالة" المعتمدة (دروب داون)، فيتوحّد اسم التشخيص مع
        // بقية النظام بدل نص حر قد لا يطابق أي نوع حالة معروف.
        unset($rules['preliminary_diagnosis']);
        $rules['preliminary_diagnosis_case_type_id'] = 'required|integer|exists:case_types,id';

        $rules['case_type_ids'] = 'required|array|min:1';
        $rules['case_type_ids.*'] = 'required|exists:case_types,id';

        $rules['estimated_costs'] = 'required|array|min:1';
        $rules['estimated_costs.*'] = 'required|numeric|min:0';

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

            $caseTypeIds = $this->input('case_type_ids');

            if (is_array($caseTypeIds) && count($caseTypeIds) > 5) {
                $validator->errors()->add(
                    'limit',
                    'You cannot submit more than 5 cases at once.'
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
