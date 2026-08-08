<?php

namespace App\Repositories;

use App\Models\Group;
use Illuminate\Support\Facades\Cache;

class GroupRepository
{
    public function getAllCached()
    {
        return Cache::remember('all_groups', 86400, function () {
            return Group::select('id', 'group_name', 'academic_year')->get()->map(function ($group) {
                $group->display_name = $group->group_name.' - '.($group->academic_year == 4 ? 'السنة الرابعة' : 'السنة الخامسة');

                return $group;
            });
        });
    }

    public function getGroupsByYearCached(int $year)
    {
        $cacheKey = "groups_year_{$year}";

        return Cache::remember($cacheKey, 86400, function () use ($year) {
            return Group::select('id', 'group_name', 'academic_year')
                ->where('academic_year', $year)
                ->get()
                ->map(function ($group) {
                    $group->display_name = $group->group_name;

                    return $group;
                });
        });
    }
}
