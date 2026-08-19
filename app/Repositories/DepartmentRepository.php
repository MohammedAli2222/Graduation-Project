<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Department;
use App\Support\CacheGroup;
use App\Support\CacheVersion;
use Illuminate\Support\Facades\Cache;

class DepartmentRepository
{
    public function getAllCached()
    {
        return Cache::remember(CacheVersion::key(CacheGroup::DEPARTMENTS, 'all_departments'), 86400, function () {
            return Department::select('id', 'name')->orderBy('name')->get();
        });
    }
}
