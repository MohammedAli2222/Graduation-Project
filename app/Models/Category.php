<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
  
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * علاقة الفئة بالمنتجات التابعة لها.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
