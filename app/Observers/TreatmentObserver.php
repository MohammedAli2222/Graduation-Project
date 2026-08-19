<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Treatment;
use App\Support\CacheGroup;
use App\Support\CacheVersion;

class TreatmentObserver
{
    public function created(Treatment $treatment): void
    {
        $this->clearCache($treatment);
    }

    public function updated(Treatment $treatment): void
    {
        $this->clearCache($treatment);
    }

    public function deleted(Treatment $treatment): void
    {
        $this->clearCache($treatment);
    }

    // إبطال مجموعة القسم كاملة (تشمل الحالات المنجزة والإحصائيات وأنواع
    // الحالات معاً بحكم آلية CacheVersion::taggedKey) عند أي تغيّر على معالجة
    // تابعة له، فحالة رئيس القسم (المكتملة/الإحصائيات) لم تكن تُبطَل إطلاقاً من قبل
    private function clearCache(Treatment $treatment): void
    {
        $departmentId = $treatment->diagnosis?->department_id;

        if ($departmentId !== null) {
            CacheVersion::bump(CacheGroup::department($departmentId));
        }
    }
}
