<?php



namespace App\Repositories\hod;

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

    public function getCaseTypesByDepartment(int $departmentId): Collection
    {
        $caseTypes = $this->model->newQuery()
            ->select('case_types.*')
            ->join('courses', 'case_types.course_id', '=', 'courses.id')
            ->where('courses.department_id', $departmentId)
            ->with('course:id,name')
            ->orderBy('courses.name')
            ->get();

        if ($caseTypes->isEmpty()) {
            return $caseTypes;
        }

        // required_count هو عدد المرات التي يجب على كل طالب على حدة إنجاز هذا
        // النوع من الحالات (وليس مجموع الإنجازات لكل الطلاب)، فالنسبة الصحيحة
        // هي: كم طالباً حقق نصابه الفردي الكامل من أصل الطلاب المسجّلين بالمقرر.
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

            return $caseType;
        });
    }

    /**
     * لكل نوع حالة: عدد المرات المُنجزة (Completed) لكل طالب على حدة —
     * أساس حساب من حقق النصاب المطلوب فعلياً.
     *
     * @return BaseCollection<int, BaseCollection<int, int>> case_type_id => [عدد إنجازات كل طالب]
     */
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

    /**
     * عدد الطلاب المسجّلين فعلياً بكل مقرر — المرجع (المقام) لحساب نسبة
     * تحقيق المتطلب داخل ذلك المقرر.
     *
     * @return BaseCollection<int, int> course_id => عدد الطلاب
     */
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
