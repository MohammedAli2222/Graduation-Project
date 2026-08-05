<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Treatment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TreatmentRepositoryInterface
{
    public function getOptimizedCompletedTreatments(int $departmentId, int $perPage = 15): LengthAwarePaginator;
    public function getDepartmentTreatmentStatistics(int $departmentId): array;
    public function findDetailedForDepartment(int $treatmentId): ?Treatment;
}
