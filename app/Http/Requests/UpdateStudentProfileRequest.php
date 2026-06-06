<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentProfileRequest extends FormRequest
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
        $user = auth()->user();
        
        $studentProfileId = $user->studentProfile?->id;

        return [
            'first_name' => 'sometimes|required|string|max:50',
            'last_name'  => 'sometimes|required|string|max:50',
            'email'      => 'sometimes|required|email|unique:users,email,' . $user->id,


            'phone'      => 'sometimes|required|string|unique:student_profiles,phone,' . $studentProfileId,
        ];
    }
}
