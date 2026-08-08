<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use App\Support\CacheVersion;

class CategoryObserver
{
    public function created(Category $category): void
    {
        $this->clearCache();
    }

    public function updated(Category $category): void
    {
        $this->clearCache();
    }

    public function deleted(Category $category): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        CacheVersion::bump('categories');
    }
}
