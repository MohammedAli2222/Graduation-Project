<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{

    protected $fillable = [
        'student_id',
        'store_id',
        'total_amount',
        'status',
        'rejection_reason',
    ];


    protected $casts = [
        'total_amount' => 'float',
        'status'       => OrderStatus::class,
    ];


    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }


    public function store(): BelongsTo
    {
        return $this->belongsTo(User::class, 'store_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
