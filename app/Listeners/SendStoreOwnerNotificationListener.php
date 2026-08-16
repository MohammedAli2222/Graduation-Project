<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\NewStoreOrderPlacedEvent;
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
            ['type' => 'new_store_order', 'order_id' => (string) $order->id]
        );
    }
}
