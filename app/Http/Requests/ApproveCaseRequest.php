<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApproveCaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * لا حقول مطلوبة: الموافقة تعني أن تشخيص الطالب صحيح كما هو، فالتشخيص
     * النهائي يُشتق تلقائياً من اسم نوع الحالة نفسه بدل نص يكتبه المعيد.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
