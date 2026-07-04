<?php

declare(strict_types=1);

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'total_amount'     => (float) $this->total_amount,
            'status'           => $this->status?->value ?? $this->status,
            'rejection_reason' => $this->rejection_reason,

            'student'          => $this->whenLoaded('student', function () {
                $profile = $this->student->studentProfile;

                return [
                    'id'    => $this->student->id,
                    'name'  => $this->student->first_name . ' ' . $this->student->last_name,
                    'phone' => $profile ? $profile->phone : 'رقم الهاتف غير متوفر',
                ];
            }),

            'items'            => OrderItemResource::collection($this->whenLoaded('orderItems')),

            'created_at'       => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'       => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
