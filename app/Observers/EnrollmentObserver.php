<?php

namespace App\Observers;

use App\Models\StudentCourseEnrollment;
use Illuminate\Support\Facades\Cache;

class EnrollmentObserver
{
    /**
     * Handle the StudentCourseEnrollment "created" event.
     */
    public function created(StudentCourseEnrollment $studentCourseEnrollment): void
    {
        //
    }

    /**
     * Handle the StudentCourseEnrollment "updated" event.
     */
    public function updated(StudentCourseEnrollment $enrollment): void
    {
        Cache::forget("case_types:student_{$enrollment->student_id}");
    }

    /**
     * Handle the StudentCourseEnrollment "deleted" event.
     */
    public function deleted(StudentCourseEnrollment $studentCourseEnrollment): void
    {
        //
    }

    /**
     * Handle the StudentCourseEnrollment "restored" event.
     */
    public function restored(StudentCourseEnrollment $studentCourseEnrollment): void
    {
        //
    }

    /**
     * Handle the StudentCourseEnrollment "force deleted" event.
     */
    public function forceDeleted(StudentCourseEnrollment $studentCourseEnrollment): void
    {
        //
    }
}
