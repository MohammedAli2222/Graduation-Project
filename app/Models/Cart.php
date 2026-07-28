<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;
    /**
     * الحقول المسموح بتعبئتها.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
    ];

    /**
     * علاقة السلة بالطالب (مالك السلة).
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * علاقة السلة بالعناصر الموجودة بداخلها.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
