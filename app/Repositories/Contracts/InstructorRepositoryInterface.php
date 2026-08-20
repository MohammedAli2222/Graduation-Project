<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface InstructorRepositoryInterface
{
    public function findInstructorById(int $instructorId): ?User;

    /**
     * معيدون لديهم طلب تفويض معلَّق موجَّه تحديداً إلى هذا القسم.
     */
    public function getPendingDelegationRequests(int $departmentId): Collection;

    /**
     * شاشة صلاحيات المعيدين: طلبات معلَّقة + معيدون ممنوحون الصلاحية فعلاً لهذا القسم.
     */
    public function getDelegationScreenInstructors(int $departmentId): Collection;
}
