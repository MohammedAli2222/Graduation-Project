<?php

namespace App\Observers;

use App\Models\CaseType;
use Illuminate\Support\Facades\Cache;

class CaseTypeObserver
{
    private function clearCaseTypesCache(CaseType $caseType): void
    {

        if ($caseType->course) {
            $year = $caseType->course->year;
            $semester = $caseType->course->semester;

            $cacheKey = "case_types:year_{$year}:semester_{$semester}";

            Cache::forget($cacheKey);
        }
    }

    /**
     * Handle the CaseType "created" event.
     */
    public function created(CaseType $caseType): void
    {
        $this->clearCaseTypesCache($caseType);
    }

    /**
     * Handle the CaseType "updated" event.
     */
    public function updated(CaseType $caseType): void
    {
        $this->clearCaseTypesCache($caseType);
    }

    /**
     * Handle the CaseType "deleted" event.
     */
    public function deleted(CaseType $caseType): void
    {
        $this->clearCaseTypesCache($caseType);
    }
}
