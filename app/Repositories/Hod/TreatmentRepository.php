<?php

declare(strict_types=1);

namespace App\Repositories\Hod;

use App\Enums\TreatmentStatus;
use App\Models\Treatment;
use App\Repositories\Contracts\TreatmentRepositoryInterface;
use Illuminate\Contracts\Pagination\Paginator;

class TreatmentRepository implements TreatmentRepositoryInterface
{
    public function __construct(
        protected Treatment $model
    ) {}

    public function getOptimizedCompletedTreatments(int $departmentId, int $perPage = 15, ?int $groupId = null): Paginator
    {
        return $this->model->newQuery()
            ->select('treatments.*')

            ->join('patient_diagnoses', 'treatments.diagnosis_id', '=', 'patient_diagnoses.id')

            ->join('case_types', 'patient_diagnoses.case_type_id', '=', 'case_types.id')
            ->join('courses', 'case_types.course_id', '=', 'courses.id')

            ->where('treatments.status', TreatmentStatus::COMPLETED->value)
            ->where('courses.department_id', $departmentId)

            // فلترة اختيارية بفئة (مجموعة) الطالب المُنفِّذ الفعلي للعلاج، عبر
            // appointments.student وليس diagnosis.suggested_by_student_id (انظر
            // الملاحظة أسفل eager load الطالب لسبب ذلك)
            ->when($groupId, function ($query) use ($groupId) {
                $query->whereHas('appointments.student.studentProfile', function ($q) use ($groupId) {
                    $q->where('group_id', $groupId);
                });
            })

            ->with([
                'diagnosis:id,patient_id,case_type_id,department_id',
                // عمود اسم المريض الفعلي في جدول patients هو full_name وليس first_name/last_name
                'diagnosis.patient:id,full_name,phone',
                'diagnosis.caseType:id,name',
                'diagnosis.department:id,name',
                // الطالب المُنفِّذ الفعلي للعلاج يُستدل من الموعد (appointments.student)
                // لا من diagnosis.suggested_by_student_id؛ فذاك العمود يمثّل فقط من
                // اقترح التشخيص أصلاً (وغالباً null لأن أغلب التشخيصات يضعها المعيد
                // مباشرة)، بينما أي طالب مؤهَّل يمكنه لاحقاً حجز الموعد وتنفيذ العلاج.
                'appointments:id,treatment_id,student_id',
                'appointments.student:id,first_name,last_name',
                'appointments.student.studentProfile:id,user_id,group_id',
                'appointments.student.studentProfile.group:id,group_name',
                'instructor:id,first_name,last_name'
            ])

            ->latest('treatments.updated_at')
            // استخدام simplePaginate بدل paginate لإلغاء استعلام COUNT(*) الإضافي
            // على الجداول المرتبطة بعدة JOIN، والذي يُعدّ العبء الأكبر في هذا الاستعلام
            ->simplePaginate($perPage);
    }

    public function findDetailedForDepartment(int $treatmentId): ?Treatment
    {
        return $this->model->newQuery()
            ->with([
                'diagnosis:id,patient_id,case_type_id,department_id,final_diagnosis,status',
                'diagnosis.patient:id,full_name,patient_code,gender,phone',
                'diagnosis.caseType:id,name,required_count',
                'diagnosis.department:id,name',
                'appointments:id,treatment_id,student_id',
                'appointments.student:id,first_name,last_name,email',
                'appointments.student.studentProfile:id,user_id,group_id',
                'appointments.student.studentProfile.group:id,group_name',
                'instructor:id,first_name,last_name,email',
                'media',
            ])
            ->find($treatmentId);
    }

    public function getDepartmentTreatmentStatistics(int $departmentId, ?int $courseId = null): array
    {
        return $this->model->newQuery()

            ->selectRaw('treatments.status, COUNT(treatments.id) as total_count')

            ->join('patient_diagnoses', 'treatments.diagnosis_id', '=', 'patient_diagnoses.id')
            ->join('case_types', 'patient_diagnoses.case_type_id', '=', 'case_types.id')
            ->join('courses', 'case_types.course_id', '=', 'courses.id')

            ->where('courses.department_id', $departmentId)
            // تصفية اختيارية بمقرر محدد ضمن القسم نفسه؛ لا تُطبَّق إن لم يُمرَّر $courseId
            ->when($courseId, fn($q) => $q->where('courses.id', $courseId))

            ->groupBy('treatments.status')

            ->pluck('total_count', 'status')
            ->toArray();
    }
}
