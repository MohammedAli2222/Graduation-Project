<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

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
        // مخزن الكاش الحالي (CACHE_STORE=file) لا يدعم الوسوم (Tags)، وسيرمي
        // BadMethodCallException عند استدعاء Cache::tags() مباشرة، مما كان يوقف
        // أي عملية إنشاء/تعديل/حذف لفئة بأكملها. نتجاهل الاستثناء بأمان هنا لأن
        // لا يوجد حالياً أي كود آخر يخزّن بيانات فعلية تحت الوسم 'categories'.
        try {
            Cache::tags(['categories'])->flush();
        } catch (\BadMethodCallException) {
            //
        }
    }
}
