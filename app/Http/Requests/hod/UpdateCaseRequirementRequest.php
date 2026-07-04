<?php


namespace App\Http\Requests\hod;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseRequirementRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true; // المصادقة ستتم في طبقة الخدمة بناءً على قسم الـ HOD
    }


    public function rules(): array
    {
        return [
            'required_count' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
