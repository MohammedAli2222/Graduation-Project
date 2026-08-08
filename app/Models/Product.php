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

    protected $with = [];


    protected static function booted(): void
    {
        static::saving(function (Product $product) {

            if ($product->quantity <= 0) {
                $product->quantity = 0;
                $product->availability_status = ProductAvailability::OUT_OF_STOCK->value;
            } elseif ($product->quantity > 0) {
                $currentStatus = $product->availability_status instanceof ProductAvailability
                    ? $product->availability_status->value
                    : $product->availability_status;

                if ($currentStatus === ProductAvailability::OUT_OF_STOCK->value) {
                    $product->availability_status = ProductAvailability::AVAILABLE->value;
                }
            }
        });
    }


    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        })
            ->when($filters['category_id'] ?? null, function ($q, $categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->when($filters['condition'] ?? null, function ($q, $condition) {
                $q->where('condition', $condition);
            });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(User::class, 'store_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'store_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function promotions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_product');
    }

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
