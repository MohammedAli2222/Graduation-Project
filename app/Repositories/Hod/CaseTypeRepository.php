<?php

declare(strict_types=1);

namespace App\Repositories\Hod;

use App\Enums\TreatmentStatus;
use App\Models\CaseType;
use App\Models\StudentCourseEnrollment;
use App\Models\Treatment;
use App\Repositories\Contracts\CaseTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

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

    public function getCaseTypesByDepartment(int $departmentId, ?int $courseId = null): Collection
    {
        $caseTypes = $this->model->newQuery()
            ->select('case_types.*')
            ->join('courses', 'case_types.course_id', '=', 'courses.id')
            ->where('courses.department_id', $departmentId)
            // تصفية اختيارية بمقرر محدد ضمن القسم نفسه؛ لا تُطبَّق إن لم يُمرَّر $courseId
            ->when($courseId, fn ($q) => $q->where('case_types.course_id', $courseId))
            ->with('course:id,name')
            ->orderBy('courses.name')
            ->get();

        if ($caseTypes->isEmpty()) {
            return $caseTypes;
        }

        $perStudentCounts = $this->getPerStudentCompletionCounts($caseTypes->pluck('id')->all());
        $enrolledStudentCounts = $this->getEnrolledStudentCounts($caseTypes->pluck('course_id')->unique()->all());

        return $caseTypes->map(function (CaseType $caseType) use ($perStudentCounts, $enrolledStudentCounts) {
            $studentCounts = $perStudentCounts->get($caseType->id, collect());
            $totalStudents = $enrolledStudentCounts->get($caseType->course_id, 0);

            $studentsMetRequirement = $studentCounts
                ->filter(fn (int $count) => $count >= $caseType->required_count)
                ->count();

            $caseType->completed_count = $studentCounts->sum();
            $caseType->students_met_requirement = $studentsMetRequirement;
            $caseType->total_students = $totalStudents;
            $caseType->progress_percentage = $totalStudents > 0
                ? (int) round(min($studentsMetRequirement / $totalStudents, 1) * 100)
                : 0;

            $caseType->students_distribution = $this->buildStudentsDistribution(
                $studentCounts,
                $totalStudents,
                $caseType->required_count,
                $studentsMetRequirement
            );

            return $caseType;
        });
    }

    /**
     * يبني توزيعاً تكرارياً (Frequency Distribution) لعدد الطلاب حسب عدد
     * الحالات التي أكملوها فعلياً من هذا النوع، كنسب مئوية من إجمالي الطلاب
     * المسجَّلين بالمقرر: من "0" (لم يكمل أي حالة) وحتى الحد المطلوب مجتمعاً
     * في "completed_all" (استوفى المتطلب بالكامل أو تجاوزه).
     *
     * @return array<string, int>
     */
    private function buildStudentsDistribution(
        BaseCollection $studentCounts,
        int $totalStudents,
        int $requiredCount,
        int $studentsMetRequirement
    ): array {
        $distribution = [];

        // الطلاب الذين لم يُكملوا أي حالة إطلاقاً لا يظهرون في $studentCounts أصلاً
        $studentsWithZeroCompletions = $totalStudents - $studentCounts->count();
        $distribution['0_completed'] = $this->toPercentageOfTotal($studentsWithZeroCompletions, $totalStudents);

        // من 1 وحتى (required_count - 1): عدد الطلاب الذين أكملوا هذا العدد بالضبط
        for ($count = 1; $count < $requiredCount; $count++) {
            $studentsWithExactCount = $studentCounts->filter(fn (int $c) => $c === $count)->count();
            $distribution["{$count}_completed"] = $this->toPercentageOfTotal($studentsWithExactCount, $totalStudents);
        }

        // الطلاب الذين استوفوا العدد المطلوب بالكامل أو تجاوزوه
        $distribution['completed_all'] = $this->toPercentageOfTotal($studentsMetRequirement, $totalStudents);

        return $distribution;
    }

    private function toPercentageOfTotal(int $count, int $totalStudents): int
    {
        return $totalStudents > 0 ? (int) round($count / $totalStudents * 100) : 0;
    }

    private function getPerStudentCompletionCounts(array $caseTypeIds): BaseCollection
    {
        return Treatment::query()
            ->join('patient_diagnoses', 'treatments.diagnosis_id', '=', 'patient_diagnoses.id')
            ->whereIn('patient_diagnoses.case_type_id', $caseTypeIds)
            ->where('treatments.status', TreatmentStatus::COMPLETED->value)
            ->selectRaw('patient_diagnoses.case_type_id, patient_diagnoses.suggested_by_student_id, COUNT(*) as completed_count')
            ->groupBy('patient_diagnoses.case_type_id', 'patient_diagnoses.suggested_by_student_id')
            ->get()
            ->groupBy('case_type_id')
            ->map(fn (BaseCollection $rows) => $rows->pluck('completed_count'));
    }

    private function getEnrolledStudentCounts(array $courseIds): BaseCollection
    {
        return StudentCourseEnrollment::query()
            ->whereIn('course_id', $courseIds)
            ->selectRaw('course_id, COUNT(DISTINCT student_id) as total')
            ->groupBy('course_id')
            ->get()
            ->pluck('total', 'course_id');
    }

    public function create(array $data): CaseType
    {
        return $this->model->create($data);
    }

    public function delete(CaseType $caseType): bool
    {
        return (bool) $caseType->delete();
    }
}
