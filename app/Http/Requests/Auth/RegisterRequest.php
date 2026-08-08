<?php

namespace App\Http\Requests\Auth;

use App\Models\Group;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => ['required', 'in:student,instructor,store_owner,department_head'],

            'phone' => 'required_if:role,student,instructor|string|max:20',

            'group_id' => 'required_if:role,student|exists:groups,id',
            'exam_number' => 'required_if:role,student|unique:student_profiles,exam_number',
            'academic_year' => ['required_if:role,student', 'integer', 'in:4,5'],
            'semester' => ['required_if:role,student', 'integer', 'in:1,2'],
            'university' => 'required_if:role,student|string|max:255',

            'specialty' => 'required_if:role,instructor',
            'specialty_year' => 'required_if:role,instructor|string',
            'group_ids' => 'required_if:role,instructor|array',
            'group_ids.*' => 'exists:groups,id',

            'department_id' => 'required_if:role,department_head|exists:departments,id',

            'store_name' => 'required_if:role,store_owner|string|max:255',
            'store_phone' => 'required_if:role,store_owner|string',
            'store_address' => 'required_if:role,store_owner|string',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isEmpty()) {

                    $role = $this->input('role');

                    if ($role === 'student') {
                        $academicYear = (int) $this->input('academic_year');
                        $groupId = $this->input('group_id');

                        $group = Group::find($groupId);

                        if ($group && (int) $group->academic_year !== $academicYear) {
                            $validator->errors()->add(
                                'group_id',
                                'عذراً، الفئة المختارة غير تابعة لسنتك الدراسية الحالية.'
                            );
                        }
                    }
                }
            },
        ];
    }
}
