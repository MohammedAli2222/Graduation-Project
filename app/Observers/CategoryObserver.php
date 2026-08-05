<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    /**
     * يشتغل تلقائياً عند إضافة فئة جديدة
     */
    public function created(Category $category): void
    {
        $this->clearCache();
    }

    /**
     * يشتغل تلقائياً عند تعديل فئة موجودة
     */
    public function updated(Category $category): void
    {
        $this->clearCache();
    }

    /**
     * يشتغل تلقائياً عند حذف فئة
     */
    public function deleted(Category $category): void
    {
        $this->clearCache();
    }

    /**
     * تفريغ وسم كاش الفئات بالكامل دفعة واحدة بدل تتبع مفتاح واحد بعينه
     */
    private function clearCache(): void
    {
        Cache::tags(['categories'])->flush();
    }
}
