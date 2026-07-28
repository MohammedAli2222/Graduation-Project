<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;
    /**
     * الحقول المسموح بتعبئتها.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
    ];

    /**
     * التحويلات التلقائية (Casts).
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * علاقة العنصر بالسلة التي ينتمي إليها.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * علاقة العنصر بالمنتج الفعلي المرتبط به.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
