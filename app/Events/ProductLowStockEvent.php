<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// يُطلق هذا الحدث عندما تعبر كمية منتج تحت حد المخزون المنخفض لأول مرة بعد عملية بيع
class ProductLowStockEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Product $product) {}
}
