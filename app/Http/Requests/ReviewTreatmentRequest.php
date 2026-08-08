<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * طلب مراجعة المعيد لعلاج أنجزه الطالب (اعتماد أو رفض).
 *
 * القاعدة الأساسية: عند الرفض يجب أن يصل الطالبَ سببٌ مكتوب، فبدونه يبقى بلا
 * معرفة بما يجب إصلاحه. كانت instructor_notes مطلوبة في الحالتين معاً، وسبب
 * الرفض لا يُخزَّن إطلاقاً رغم وجود العمود rejection_reason في جدول treatments.
 * الآن نفصل بينهما: ملاحظات تقييمية اختيارية + سبب رفض إلزامي عند الرفض.
 */
class ReviewTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'treatment_id' => ['required', 'integer', 'exists:treatments,id'],
            'action' => ['required', 'in:approve,reject'],

            // ملاحظات تقييمية اختيارية تُعرض للطالب في الحالتين.
            'instructor_notes' => ['nullable', 'string', 'max:1000'],

            // سبب الرفض إلزامي عند الرفض فقط، وممنوع مع الاعتماد حتى لا
            // يُخزَّن سبب رفض على حالة معتمدة.
            'rejection_reason' => [
                'required_if:action,reject',
                'prohibited_if:action,approve',
                'nullable',
                'string',
                'min:5',
                'max:1000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'treatment_id.required' => 'The treatment case ID is required.',
            'treatment_id.exists' => 'The selected treatment record does not exist in our database.',

            'action.required' => 'The review action (approve or reject) must be specified.',
            'action.in' => 'The review action must be either "approve" or "reject" only.',

            'instructor_notes.string' => 'The instructor notes field must be a valid text string.',
            'instructor_notes.max' => 'The instructor notes field must not exceed 1000 characters.',

            'rejection_reason.required_if' => 'A rejection reason is mandatory when rejecting a treatment.',
            'rejection_reason.prohibited_if' => 'A rejection reason cannot be provided when approving a treatment.',
            'rejection_reason.min' => 'The rejection reason must be at least 5 characters so the student can act on it.',
            'rejection_reason.max' => 'The rejection reason must not exceed 1000 characters.',
        ];
    }
}
