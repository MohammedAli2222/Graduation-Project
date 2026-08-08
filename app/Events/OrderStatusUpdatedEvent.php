<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// يُطلق هذا الحدث عند تحديث صاحب المتجر لحالة طلب أحد الطلاب
class OrderStatusUpdatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
