<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\NewStoreOrderPlacedEvent;
use App\Services\FirebaseNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

// يعمل ضمن قائمة انتظار (Queue) حتى لا يؤخر إرسال الإشعار استجابة الـ API الرئيسية
class SendStoreOwnerNotificationListener implements ShouldQueue
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
