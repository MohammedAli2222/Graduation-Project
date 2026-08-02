<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Enums\ProductAvailability;
use App\Enums\ProductCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreProductRequest extends FormRequest
{
    /**
     * التحقق من الصلاحيات.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'category_id'         => ['sometimes', 'integer', 'exists:categories,id'],
            'name'                => ['sometimes', 'string', 'max:255'],
            'description'         => ['sometimes', 'string'],
            'price'               => ['sometimes', 'numeric', 'min:0.01', 'max:999999.99'],
            'quantity'            => ['sometimes', 'integer', 'min:0'],
            'brand'               => ['nullable', 'string', 'max:255'],

            'availability_status' => ['sometimes', Rule::enum(ProductAvailability::class)],
            'condition'           => ['sometimes', Rule::enum(ProductCondition::class)],

            'images'              => ['nullable', 'array', 'max:5'],
            'images.*'            => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }
}
