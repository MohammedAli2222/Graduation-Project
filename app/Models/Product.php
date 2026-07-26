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
        'quantity',
    ];

    protected $casts = [
        'price'               => 'float',
        'availability_status' => ProductAvailability::class,
        'condition'           => ProductCondition::class,
        'quantity' => 'integer',
    ];

    protected $with = ['media'];


    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            
            // 1. حماية المخزون: منع الكمية من أن تكون أقل من الصفر أبداً
            if ($product->quantity <= 0) {
                $product->quantity = 0;
                $product->availability_status = ProductAvailability::OUT_OF_STOCK->value;
            } 
            
            elseif ($product->quantity > 0) {
                // استخراج القيمة النصية للحالة سواء كانت Enum Object أو String
                $currentStatus = $product->availability_status instanceof ProductAvailability 
                    ? $product->availability_status->value 
                    : $product->availability_status;

                // إذا كانت الحالة الحالية "نفد من المخزون"، نقوم بإعادتها فوراً إلى "متوفر"
                if ($currentStatus === ProductAvailability::OUT_OF_STOCK->value) {
                    $product->availability_status = ProductAvailability::AVAILABLE->value;
                }
            }
        });
    }

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
