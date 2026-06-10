<?php

namespace App\Repositories\Contracts;

use App\Models\CaseType;

use Illuminate\Database\Eloquent\Collection;
interface CaseTypeRepositoryInterface
{

    public function findByIdWithCourse(int $id): ?CaseType;
    public function updateRequiredCount(CaseType $caseType, int $newCount): bool;
    public function getCaseTypesByDepartment(int $departmentId): Collection;
}
