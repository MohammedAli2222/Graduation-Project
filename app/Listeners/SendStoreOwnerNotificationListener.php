<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\NewStoreOrderPlacedEvent;
use App\Events\ProductLowStockEvent;
use App\Services\FirebaseNotificationService;

class SendStoreOwnerNotificationListener
{
    public function __construct(protected FirebaseNotificationService $notificationService) {}

    // إشعار صاحب المتجر بوصول طلب شراء جديد من أحد الطلاب
    public function handle(NewStoreOrderPlacedEvent $event): void
    {
        $order = $event->order;

        $this->notificationService->sendNotificationToUser(
            $order->store_id,
            'طلب جديد',
            'لديك طلب شراء جديد رقم ' . $order->id . ' بقيمة ' . $order->total_amount . '.',
            ['type' => 'new_store_order', 'order_id' => (string) $order->id],
            'new_store_order'
        );
    }

    // إشعار صاحب المتجر بأن كمية أحد منتجاته أوشكت على النفاد
    public function handleLowStock(ProductLowStockEvent $event): void
    {
        $product = $event->product;

        $this->notificationService->sendNotificationToUser(
            $product->store_id,
            'كمية منخفضة',
            'الكمية المتبقية من "' . $product->name . '" أوشكت على النفاد (متبقٍ ' . $product->quantity . ').',
            ['type' => 'low_stock', 'product_id' => (string) $product->id],
            'low_stock'
        );
    }
}
