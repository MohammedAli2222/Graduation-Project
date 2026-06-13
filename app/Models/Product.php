<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductAvailability;
use App\Enums\ProductCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;


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


    public function store(): BelongsTo
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
            ->queued(); // تنفيذ في الخلفية عبر الطوابير Queue لعدم تأخير الاستجابة

        $this->addMediaConversion('optimized')
            ->width(800)
            ->height(800)
            ->queued();
    }
}
