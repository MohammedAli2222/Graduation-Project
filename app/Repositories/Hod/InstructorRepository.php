<?php

declare(strict_types=1);

namespace App\Repositories\Hod;

use App\Models\User;
use App\Repositories\Contracts\InstructorRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class InstructorRepository implements InstructorRepositoryInterface
{
    public function __construct(
        protected User $model
    ) {}


    public function findInstructorById(int $instructorId): ?User
    {
        return $this->model->newQuery()
            ->role('instructor')
            ->find($instructorId);
    }

    public function getPendingDelegationRequests(int $departmentId): Collection
    {
        return $this->model->newQuery()
            ->role('instructor')
            ->whereHas('instructorProfile', function ($query) use ($departmentId) {
                $query->where('requested_department_id', $departmentId);
            })
            ->with('instructorProfile')
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->get();
    }

    /**
     * شاشة "صلاحيات المعيدين": تجمع من ينتظر موافقة على طلبه (requested_department_id)
     * ومن مُنح الصلاحية فعلاً لهذا القسم مسبقاً (department_id + يملك الصلاحية حالياً)،
     * وإلا يختفي المعيد من الشاشة فور منحه الصلاحية فلا يعود ممكناً سحبها منه لاحقاً.
     */
    public function getDelegationScreenInstructors(int $departmentId): Collection
    {
        return $this->model->newQuery()
            ->role('instructor')
            ->where(function ($query) use ($departmentId): void {
                $query->whereHas('instructorProfile', fn ($q) => $q->where('requested_department_id', $departmentId))
                    ->orWhere(fn ($q) => $q
                        ->whereHas('instructorProfile', fn ($qq) => $qq->where('department_id', $departmentId))
                        ->whereHas('permissions', fn ($qq) => $qq->where('name', 'view-department-treatments'))
                    );
            })
            ->with('instructorProfile')
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->get();
    }
}
