<?php


namespace App\Http\Requests\Hod;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseRequirementRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'required_count' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
