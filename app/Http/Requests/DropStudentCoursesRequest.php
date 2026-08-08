<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;


class DropStudentCoursesRequest extends FormRequest
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
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['required', 'integer', 'distinct', 'exists:courses,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'course_ids.required' => 'You must specify at least one course to drop.',
            'course_ids.min' => 'You must specify at least one course to drop.',
            'course_ids.*.exists' => 'One or more selected courses do not exist.',
            'course_ids.*.distinct' => 'Duplicate course IDs are not allowed.',
        ];
    }
}
