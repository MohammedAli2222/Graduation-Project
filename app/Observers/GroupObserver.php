<?php

namespace App\Observers;

use App\Models\Group;
use Illuminate\Support\Facades\Cache;

class GroupObserver
{
    public function created(Group $group): void
    {
        $this->clearCache();
    }

    public function updated(Group $group): void
    {
        $this->clearCache();
    }

    public function deleted(Group $group): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        Cache::forget('groups_year_4');
        Cache::forget('groups_year_5');
        Cache::forget('all_groups');
    }
}
