<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Promotion extends Model
{

    protected $fillable = [
        'store_id',
        'title',
        'description',
        'discount_percentage',
        'start_date',
        'end_date',
        'is_active',
    ];


    protected $casts = [
        'discount_percentage' => 'float',
        'start_date'          => 'datetime',
        'end_date'            => 'datetime',
        'is_active'           => 'boolean',
    ];


    public function store(): BelongsTo
    {
        return $this->belongsTo(User::class, 'store_id');
    }

 
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_product');
    }
}
