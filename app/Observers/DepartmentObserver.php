<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Department;
use Illuminate\Support\Facades\Cache;

class DepartmentObserver
{
    /**
     * يشتغل تلقائياً عند إضافة قسم جديد
     */
    public function created(Department $department): void
    {
        $this->clearCache($department);
    }

    /**
     * يشتغل تلقائياً عند تعديل بيانات القسم (كعدد الكراسي total_chairs)
     */
    public function updated(Department $department): void
    {
        $this->clearCache($department);
    }

    /**
     * يشتغل تلقائياً عند حذف القسم
     */
    public function deleted(Department $department): void
    {
        $this->clearCache($department);
    }

    /**
     * حذف مفتاح كاش إعدادات هذا القسم تحديداً (نفس المفتاح المستخدم في AppointmentRepository)
     */
    private function clearCache(Department $department): void
    {
        Cache::forget("department_{$department->id}_config");
    }
}
