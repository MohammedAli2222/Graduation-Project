<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// يُطلق هذا الحدث عند إتمام طالب لعملية شراء جديدة من أحد المتاجر
class NewStoreOrderPlacedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
