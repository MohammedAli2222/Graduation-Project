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
}
