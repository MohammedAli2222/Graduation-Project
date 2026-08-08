<?php

namespace App\Http\Requests\hod;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaseTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'required_count' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
