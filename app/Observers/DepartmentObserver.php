<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Department;
use Illuminate\Support\Facades\Cache;

class DepartmentObserver
{
    public function created(Department $department): void
    {
        $this->clearCache($department);
    }

    public function updated(Department $department): void
    {
        $this->clearCache($department);
    }

    public function deleted(Department $department): void
    {
        $this->clearCache($department);
    }

    private function clearCache(Department $department): void
    {
        Cache::forget("department_{$department->id}_config");
    }
}
