<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Treatment;
use Illuminate\Contracts\Pagination\Paginator;

interface TreatmentRepositoryInterface
{
    public function getOptimizedCompletedTreatments(int $departmentId, int $perPage = 15, ?int $groupId = null): Paginator;
    public function getDepartmentTreatmentStatistics(int $departmentId, ?int $courseId = null): array;
    public function findDetailedForDepartment(int $treatmentId): ?Treatment;
}
