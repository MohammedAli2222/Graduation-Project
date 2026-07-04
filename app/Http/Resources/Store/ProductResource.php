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

            // إضافة رقم الشخص/المتجر الذي أضاف المنتج
            'seller_id'           => $this->store_id,

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
           // الجدار الأمني/البيانات: إرجاع بيانات البائع مع رقم الهاتف الصحيح
            'seller'              => $this->whenLoaded('seller', function () {

                $phone = 'رقم الهاتف غير متوفر';

                if ($this->seller->relationLoaded('storeProfile') && $this->seller->storeProfile) {
                    $phone = $this->seller->storeProfile->store_phone ?? $phone;
                }
                // إذا لم يكن متجراً، نتحقق بشكل صريح من بروفايل الطالب
                elseif ($this->seller->relationLoaded('studentProfile') && $this->seller->studentProfile) {
                    $phone = $this->seller->studentProfile->phone ?? $phone;
                }

                return [
                    'id'    => $this->seller->id,
                    'name'  => trim($this->seller->first_name . ' ' . $this->seller->last_name),
                    'phone' => $phone,
                ];
            }),

            // يفترض أنك تستخدم مورد للصور، أو ترجعها كمصفوفة حسب طريقتك
            'images'              => $this->whenLoaded('media', function () {
                return $this->media->map(fn($media) => $media->getUrl());
            }, []),

            'created_at'          => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'          => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
