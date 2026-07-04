<?php


namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'product_id'   => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn() => $this->product->name),
            'quantity'     => (int) $this->quantity,
            'unit_price'   => (float) $this->unit_price,
            'subtotal'     => (float) $this->subtotal,
        ];
    }
}
