<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * طلب إنهاء العلاج من طرف الطالب.
 *
 * صور ما بعد المعالجة إلزامية دائماً هنا، لأنها الدليل الذي يقيّم عليه المعيد
 * الحالة قبل اعتمادها. سقف الصور مطابق لصور ما قبل المعالجة (٥) حتى تبقى
 * تجربة الرفع في التطبيق واحدة في الخطوتين.
 */
class CompleteTreatmentRequest extends FormRequest
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
            'after_images' => ['required', 'array', 'min:1', 'max:5'],
            'after_images.*' => ['image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'treatment_id.required' => 'The treatment ID is required.',
            'treatment_id.exists' => 'The selected treatment record does not exist.',

            'after_images.required' => 'After-treatment images are mandatory to submit the case for instructor review.',
            'after_images.min' => 'At least one after-treatment image must be uploaded.',
            'after_images.max' => 'You cannot upload more than 5 after-treatment images.',
            'after_images.*.image' => 'Each after-treatment file must be a valid image.',
            'after_images.*.mimes' => 'After-treatment images must be of type: jpeg, png, jpg.',
            'after_images.*.max' => 'Each after-treatment image must not exceed 5 MB.',
        ];
    }
}
