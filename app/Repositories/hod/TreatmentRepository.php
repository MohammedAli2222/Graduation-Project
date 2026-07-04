<?php

declare(strict_types=1);

namespace App\Repositories\hod;

use App\Enums\TreatmentStatus;
use App\Models\Treatment;
use App\Repositories\Contracts\TreatmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TreatmentRepository implements TreatmentRepositoryInterface
{
    /**
     * حقن نموذج المعالجة.
     */
    public function __construct(
        protected Treatment $model
    ) {}

    public function getOptimizedCompletedTreatments(int $departmentId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->select('treatments.*')

            // 💡 التعديل هنا: استخدام اسم الجدول الصحيح patient_diagnoses
            ->join('patient_diagnoses', 'treatments.diagnosis_id', '=', 'patient_diagnoses.id')

            // 💡 التعديل هنا: ربط case_types مع patient_diagnoses
            ->join('case_types', 'patient_diagnoses.case_type_id', '=', 'case_types.id')
            ->join('courses', 'case_types.course_id', '=', 'courses.id')

            ->where('treatments.status', TreatmentStatus::COMPLETED->value)
            ->where('courses.department_id', $departmentId)

            ->with([
                'diagnosis:id,patient_id,student_id,case_type_id',
                'diagnosis.student:id,first_name,last_name',
                'diagnosis.patient:id,first_name,last_name',
                'diagnosis.caseType:id,name',
                'instructor:id,first_name,last_name'
            ])

            ->latest('treatments.updated_at')
            ->paginate($perPage);
    }

    public function getDepartmentTreatmentStatistics(int $departmentId): array
    {
        return $this->model->newQuery()

        ->selectRaw('treatments.status, COUNT(treatments.id) as total_count')

            ->join('patient_diagnoses', 'treatments.diagnosis_id', '=', 'patient_diagnoses.id')
            ->join('case_types', 'patient_diagnoses.case_type_id', '=', 'case_types.id')
            ->join('courses', 'case_types.course_id', '=', 'courses.id')

            ->where('courses.department_id', $departmentId)

            ->groupBy('treatments.status')

            ->pluck('total_count', 'status')
            ->toArray();
    }
}
