<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'discount_percentage' => ['required', 'numeric', 'min:1', 'max:99.99'],
            'start_date'          => ['required', 'date', 'date_format:Y-m-d H:i:s'],
            'end_date'            => ['required', 'date', 'date_format:Y-m-d H:i:s', 'after:start_date'],
            'is_active'           => ['boolean'],
            'product_ids'         => ['required', 'array', 'min:1'],
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
