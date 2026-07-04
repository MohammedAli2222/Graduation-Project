<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'           => ['required', Rule::enum(OrderStatus::class)],
            'rejection_reason' => ['required_if:status,' . OrderStatus::REJECTED->value, 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required_if' => 'يجب كتابة سبب الرفض عند رفض الطلب.',
        ];
    }
}
