<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductAvailability;
use App\Enums\ProductCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;


    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'description',
        'price',
        'brand',
        'availability_status',
        'condition',
    ];

    protected $casts = [
        'price'               => 'float',
        'availability_status' => ProductAvailability::class,
        'condition'           => ProductCondition::class,
    ];

    protected $with = ['media'];

    /**
     * علاقة المنتج بصاحب المتجر الرسمي.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(User::class, 'store_id');
    }

    /**
     * علاقة المنتج بالبائع (الطالب أو المتجر) - تستخدم في سياق الـ Marketplace.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'store_id');
    }

    /**
     * علاقة المنتج بالفئة.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * علاقة المنتج بالعروض الترويجية.
     */
    public function promotions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_product');
    }

    /**
     * تسجيل تحويلات الصور المصغرة والمحسنة.
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->queued();

        $this->addMediaConversion('optimized')
            ->width(800)
            ->height(800)
            ->queued();
    }
}
