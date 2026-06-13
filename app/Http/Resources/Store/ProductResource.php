<?php

declare(strict_types=1);

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'description'         => $this->description,
            'price'               => (float) $this->price,
            'brand'               => $this->brand,

            'availability_status' => $this->availability_status?->value ?? $this->availability_status,
            'condition'           => $this->condition?->value ?? $this->condition,

            'category'            => $this->whenLoaded('category', function () {
                return [
                    'id'   => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),

            'images'              => $this->getMedia('products')->map(function ($media) {
                return [
                    'id'           => $media->id,
                    'original_url' => $media->getUrl(),
                    'thumb_url'    => $media->getUrl('thumb'),
                ];
            }),

            'created_at'          => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'          => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
