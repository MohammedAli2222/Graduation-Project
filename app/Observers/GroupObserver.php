<?php

namespace App\Observers;

use App\Models\Group;
use Illuminate\Support\Facades\Cache;

class GroupObserver
{
    /**
     * يشتغل تلقائياً عند إضافة فئة جديدة
     */
    public function created(Group $group): void
    {
        $this->clearCache();
    }

    /**
     * يشتغل تلقائياً عند تعديل فئة موجودة
     */
    public function updated(Group $group): void
    {
        $this->clearCache();
    }

    /**
     * يشتغل تلقائياً عند حذف فئة
     */
    public function deleted(Group $group): void
    {
        $this->clearCache();
    }

    /**
     * دالة مخصصة لتنظيف مفتاح الكاش من ريديس
     */
    private function clearCache(): void
    {
        Cache::forget('groups_year_4');
        Cache::forget('groups_year_5');
        Cache::forget('all_groups');
    }
}
