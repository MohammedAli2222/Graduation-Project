<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Enums\ProductAvailability;
use App\Enums\ProductCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing([
            'availability_status' => ProductAvailability::AVAILABLE->value,
        ]);
    }

    public function rules(): array
    {
        return [
            'category_id'         => ['required', 'integer', 'exists:categories,id'],
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['required', 'string'],
            'price'               => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'brand'               => ['nullable', 'string', 'max:255'],
            'quantity'            => ['required', 'integer', 'min:1'],

            'availability_status' => ['required', Rule::enum(ProductAvailability::class)],
            'condition'           => ['required', Rule::enum(ProductCondition::class)],

            'images'              => ['nullable', 'array', 'max:5'],
            'images.*'            => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }
}
