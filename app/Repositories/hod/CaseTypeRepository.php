<?php



namespace App\Repositories\hod;

use App\Models\CaseType;
use App\Repositories\Contracts\CaseTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CaseTypeRepository implements CaseTypeRepositoryInterface
{
    public function __construct(
        protected CaseType $model
    ) {}

    public function findByIdWithCourse(int $id): ?CaseType
    {
        return $this->model->with('course')->find($id);
    }

    public function updateRequiredCount(CaseType $caseType, int $newCount): bool
    {
        return $caseType->update(['required_count' => $newCount]);
    }

    public function getCaseTypesByDepartment(int $departmentId): Collection
    {
        return $this->model->newQuery()
            ->select('case_types.*')
            ->join('courses', 'case_types.course_id', '=', 'courses.id')
            ->where('courses.department_id', $departmentId)
            ->with('course:id,name')
            ->orderBy('courses.name')
            ->get();
    }
}
