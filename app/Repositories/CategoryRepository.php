<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Support\CacheGroup;
use App\Support\CacheVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryRepository
{
    private const CACHE_KEY = 'categories:dropdown';

    private const CACHE_TTL_SECONDS = 86400;

    public function getDropdown(): Collection
    {
        return Cache::remember(
            CacheVersion::key(CacheGroup::CATEGORIES, self::CACHE_KEY),
            self::CACHE_TTL_SECONDS,
            fn () => Category::select('id', 'name')->get()
        );
    }
}
