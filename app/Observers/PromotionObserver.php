<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Promotion;
use App\Support\CacheGroup;
use App\Support\CacheVersion;

class PromotionObserver
{
    public function created(Promotion $promotion): void
    {
        $this->clearCache($promotion);
    }

    public function updated(Promotion $promotion): void
    {
        $this->clearCache($promotion);
    }

    public function deleted(Promotion $promotion): void
    {
        $this->clearCache($promotion);
    }

    // نسبة الخصم تُعرض الآن ضمن قائمة منتجات المتجر (مصدرها علاقة products
    // على العرض)، وتلك القائمة مخزَّنة كاش ليوم كامل — فبدون إبطالها هنا
    // يبقى الفرونت يرى بيانات عرض قديمة (أو غياب عرض حُذف) حتى نهاية اليوم.
    private function clearCache(Promotion $promotion): void
    {
        CacheVersion::bump(CacheGroup::store($promotion->store_id));
    }
}
