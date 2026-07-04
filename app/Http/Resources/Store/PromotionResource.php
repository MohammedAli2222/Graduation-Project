<?php

declare(strict_types=1);

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'title'               => $this->title,
            'description'         => $this->description,
            'discount_percentage' => (float) $this->discount_percentage,
            'start_date'          => $this->start_date?->format('Y-m-d H:i:s'),
            'end_date'            => $this->end_date?->format('Y-m-d H:i:s'),
            'is_active'           => (bool) $this->is_active,

            'products_count'      => $this->whenCounted('products'),

            'products'            => ProductResource::collection($this->whenLoaded('products')),

            'created_at'          => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
