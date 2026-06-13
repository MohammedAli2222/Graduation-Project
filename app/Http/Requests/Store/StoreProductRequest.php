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


    public function rules(): array
    {
        return [
            'category_id'         => ['required', 'integer', 'exists:categories,id'],
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['required', 'string'],
            'price'               => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'brand'               => ['nullable', 'string', 'max:255'],

            // التحقق المتقدم من الـ Enums
            'availability_status' => ['required', Rule::enum(ProductAvailability::class)],
            'condition'           => ['required', Rule::enum(ProductCondition::class)],

            'images'              => ['nullable', 'array', 'max:5'], // حد أقصى 5 صور للمنتج
            'images.*'            => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // 2MB كحد أقصى لكل صورة
        ];
    }
}
