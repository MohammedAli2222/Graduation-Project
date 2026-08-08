<?php

declare(strict_types=1);

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->product_id,
            'quantity'   => (int) $this->quantity,

            'product'    => $this->whenLoaded('product', function () {
                return [
                    'name'                => $this->product->name,
                    'price'               => (float) $this->product->price,
                    'availability_status' => $this->product->availability_status,
                    'condition'           => $this->product->condition,
                ];
            }),

            'subtotal'   => $this->relationLoaded('product')
                            ? (float) ($this->quantity * $this->product->price)
                            : 0,
        ];
    }
}
