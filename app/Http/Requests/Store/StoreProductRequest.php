<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Enums\ProductAvailability;
use App\Enums\ProductCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرحاً له بالقيام بهذا الطلب.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * تجهيز البيانات قبل خضوعها للتحقق.
     * هنا نقوم بوضع القيم الافتراضية بشكل نظيف.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing([
            // إذا لم يتم إرسال حالة التوفر، اجعلها "متوفر" افتراضياً
            'availability_status' => ProductAvailability::AVAILABLE->value,
        ]);
    }

    /**
     * قواعد التحقق الخاصة بالطلب.
     */
    public function rules(): array
    {
        return [
            'category_id'         => ['required', 'integer', 'exists:categories,id'],
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['required', 'string'],
            'price'               => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'brand'               => ['nullable', 'string', 'max:255'],
            'quantity'            => ['required', 'integer', 'min:1'],

            // التحقق المتقدم من الـ Enums (سيمر بنجاح دائماً بفضل القيمة الافتراضية في الأعلى)
            'availability_status' => ['required', Rule::enum(ProductAvailability::class)],
            'condition'           => ['required', Rule::enum(ProductCondition::class)],

            'images'              => ['nullable', 'array', 'max:5'], // حد أقصى 5 صور للمنتج
            'images.*'            => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // 2MB كحد أقصى لكل صورة
        ];
    }
}
