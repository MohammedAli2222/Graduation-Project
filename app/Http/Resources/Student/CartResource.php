<?php

declare(strict_types=1);

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // حساب إجمالي السلة برمجياً
        $totalPrice = 0;
        if ($this->relationLoaded('items')) {
            foreach ($this->items as $item) {
                if ($item->relationLoaded('product')) {
                    $totalPrice += $item->quantity * $item->product->price;
                }
            }
        }

        return [
            'id'          => $this->id,
            'student_id'  => $this->student_id,
            'total_price' => (float) $totalPrice, // إجمالي تكلفة السلة
            'items'       => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
