<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EnrollStudentCoursesRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرحاً له بتنفيذ هذا الطلب
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق من صحة معرّفات المقررات المطلوب التسجيل بها يدوياً
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'course_ids' => 'required|array',
            'course_ids.*' => 'required|integer|exists:courses,id',
        ];
    }
}
