<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

  
    public function rules(): array
    {
        return [
            'title'               => ['sometimes', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'discount_percentage' => ['sometimes', 'numeric', 'min:1', 'max:99.99'],
            'start_date'          => ['sometimes', 'date', 'date_format:Y-m-d H:i:s'],
            'end_date'            => ['sometimes', 'date', 'date_format:Y-m-d H:i:s', 'after:start_date'],
            'is_active'           => ['sometimes', 'boolean'],
            'product_ids'         => ['sometimes', 'array', 'min:1'],
            'product_ids.*'       => ['integer', 'exists:products,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after' => 'تاريخ انتهاء العرض يجب أن يكون بعد تاريخ البدء.',
        ];
    }
}
