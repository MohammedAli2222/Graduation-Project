<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
        return [
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|min:8',
            'role'         => ['required', 'in:student,instructor,store_owner,department_head'],

            // دمج الهاتف للطالب والمعيد
            'phone'        => 'required_if:role,student,instructor|string|max:20',

            // حقول الطالب
            'group_id'      => 'required_if:role,student|exists:groups,id',
            'exam_number'   => 'required_if:role,student|unique:student_profiles,exam_number',
            'academic_year' => ['required_if:role,student', 'integer', 'in:4,5'],
            'semester'      => ['required_if:role,student', 'integer', 'in:1,2'],
            'university'    => 'required_if:role,student|string|max:255',

            // حقول المعيد
            'specialty'      => 'required_if:role,instructor',
            'specialty_year' => 'required_if:role,instructor|string',
            'group_ids'      => 'required_if:role,instructor|array',
            'group_ids.*'    => 'exists:groups,id',

            // حقول رئيس القسم
            'department_id' => 'required_if:role,department_head|exists:departments,id',

            // حقول المتجر
            'store_name'    => 'required_if:role,store_owner|string|max:255',
            'store_phone'   => 'required_if:role,store_owner|string',
            'store_address' => 'required_if:role,store_owner|string',
        ];
    }
}
