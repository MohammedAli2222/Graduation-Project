<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\TreatmentStatus;
use App\Models\Treatment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TreatmentRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Treatment
    {
        return Treatment::create($data);
    }

    public function find(int|string $id): ?Treatment
    {
        return Treatment::with('diagnosis')->find($id);
    }

    /**
     * جلب العلاج مع قفل صفّه حتى نهاية المعاملة — يُستعمل في المراجعة لمنع
     * تقييمين متزامنين لنفس الحالة.
     */
    public function findForUpdate(int $id): ?Treatment
    {
        return Treatment::query()
            ->with('diagnosis')
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }

    public function updateStatus(int $treatmentId, string $status): bool
    {
        return (bool) Treatment::where('id', $treatmentId)->update(['status' => $status]);
    }

    /**
     * معرّف المستخدم (الطالب) صاحب العلاج، مأخوذاً من أول موعد ضمن الحالة.
     *
     * نحتاجه لتصفير كاش إحصائياته بعد كل تغيير يمسّ عدّاد المتطلبات، ولا
     * يوجد عمود student_id مباشر على جدول treatments.
     */
    public function getOwningStudentId(Treatment $treatment): ?int
    {
        $studentId = DB::table('appointments')
            ->where('treatment_id', $treatment->id)
            ->orderBy('id')
            ->value('student_id');

        if ($studentId === null && $treatment->diagnosis_id !== null) {
            // احتياط: مواعيد لم تُربط بالعلاج بعد، فنرجع لأول موعد على التشخيص.
            $studentId = DB::table('appointments')
                ->where('diagnosis_id', $treatment->diagnosis_id)
                ->orderBy('id')
                ->value('student_id');
        }

        return $studentId !== null ? (int) $studentId : null;
    }

    public function getHistoryByPatientId(int $patientId): Collection
    {
        return Treatment::whereHas('diagnosis', function ($query) use ($patientId): void {
            $query->where('patient_id', $patientId);
        })
            ->where('status', TreatmentStatus::COMPLETED->value)
            ->with([
                'diagnosis' => function ($query): void {
                    $query->select('id', 'patient_id', 'case_type_id', 'department_id', 'final_diagnosis', 'status', 'created_at');
                },
                'appointments' => function ($query): void {
                    $query->select('id', 'treatment_id', 'appointment_date', 'status', 'slot_number', 'created_at', 'updated_at')
                        ->orderBy('appointment_date', 'desc');
                },
            ])
            ->latest('id')
            ->get();
    }

    /**
     * Endpoint B — العلاجات التي أنهاها الطلاب وتنتظر تقييم المعيد فقط.
     *
     * لا تتضمن أبداً مرضى بانتظار وضع خطة تشخيص (تلك مسؤولية Endpoint A)،
     * لأن الشرط الوحيد هنا هو حالة العلاج waiting_instructor_approval.
     *
     * تحسين الاستعلام:
     * - whereExists مباشر على الجداول بدل سلسلة whereHas المتداخلة (٤ مستويات)
     *   التي كانت تولّد استعلامات فرعية متشعّبة وبطيئة.
     * - تقييد المواعيد المحمَّلة بأعمدة محددة، فالمورد يحتاج أول موعد فقط.
     */
    public function getPendingApprovalsListForInstructor(int $instructorProfileId, int $perPage = 10, ?int $groupId = null): LengthAwarePaginator
    {
        return Treatment::query()
            ->where('treatments.status', TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL->value)
            ->whereExists(function ($query) use ($instructorProfileId, $groupId): void {
                $query->select(DB::raw(1))
                    ->from('appointments')
                    ->join('student_profiles', 'student_profiles.user_id', '=', 'appointments.student_id')
                    ->join('group_instructor', 'group_instructor.group_id', '=', 'student_profiles.group_id')
                    ->whereColumn('appointments.diagnosis_id', 'treatments.diagnosis_id')
                    ->where('group_instructor.instructor_profile_id', $instructorProfileId)
                    // فلترة اختيارية بفئة (مجموعة) الطالب؛ ضمن نفس subquery الفحص الأصلي
                    // أصلاً، فكل فئة ممكن تظهر هون هي أصلاً من فئات هذا المعيد المُشرف عليها
                    ->when($groupId, fn ($q) => $q->where('student_profiles.group_id', $groupId));
            })
            ->with([
                'diagnosis:id,patient_id,case_type_id',
                'diagnosis.caseType:id,name',
                'diagnosis.appointments' => function ($query): void {
                    $query->select('id', 'diagnosis_id', 'student_id', 'patient_id')
                        ->orderBy('id');
                },
                'diagnosis.appointments.student:id,first_name,last_name',
                'diagnosis.appointments.student.studentProfile:id,user_id,group_id',
                'diagnosis.appointments.student.studentProfile.group:id,group_name',
                'diagnosis.appointments.patient:id,full_name',
            ])
            ->latest('treatments.updated_at')
            ->paginate($perPage);
    }

    public function getTreatmentDetailsForInstructor(int $treatmentId, int $instructorProfileId): ?Treatment
    {
        return Treatment::query()
            ->whereKey($treatmentId)
            ->whereExists(function ($query) use ($instructorProfileId): void {
                $query->select(DB::raw(1))
                    ->from('appointments')
                    ->join('student_profiles', 'student_profiles.user_id', '=', 'appointments.student_id')
                    ->join('group_instructor', 'group_instructor.group_id', '=', 'student_profiles.group_id')
                    ->whereColumn('appointments.diagnosis_id', 'treatments.diagnosis_id')
                    ->where('group_instructor.instructor_profile_id', $instructorProfileId);
            })
            ->with([
                'media',
                'diagnosis.appointments.student',
                'diagnosis.appointments.patient',
                'appointments',
                'instructor',
            ])
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStudentProgressStats(int $userId): ?array
    {
        return Cache::remember("student_progress_stats_{$userId}", 300, function () use ($userId): ?array {
            return $this->fetchStudentProgressStats($userId);
        });
    }

    public function clearStudentProgressCache(int $userId): void
    {
        Cache::forget("student_progress_stats_{$userId}");
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchStudentProgressStats(int $userId): ?array
    {
        $studentProfile = DB::table('student_profiles')->where('user_id', $userId)->first();

        if (! $studentProfile) {
            return null;
        }

        $profileId = $studentProfile->id;

        // ملكية العلاج تُحدَّد حصراً عبر appointments.treatment_id + student_id،
        // نفس المعيار المعتمد في TreatmentService::completeTreatmentFromStudent
        // و TreatmentRepository::getOwningStudentId. الربط السابق كان يصل إلى
        // treatments عبر diagnosis_id فقط دون قيد الطالب، فكان يحتسب علاجات
        // طلاب آخرين أنجزوا حالات من نفس نوع الحالة (تسرّب بيانات بين الطلاب).
        $treatmentsCount = DB::table('treatments')
            ->join('appointments', function ($join) use ($userId): void {
                $join->on('appointments.treatment_id', '=', 'treatments.id')
                    ->where('appointments.student_id', $userId);
            })
            ->join('patient_diagnoses', 'treatments.diagnosis_id', '=', 'patient_diagnoses.id')
            ->join('case_types', 'patient_diagnoses.case_type_id', '=', 'case_types.id')
            ->join('student_course_enrollments', function ($join) use ($profileId): void {
                $join->on('case_types.course_id', '=', 'student_course_enrollments.course_id')
                    ->where('student_course_enrollments.student_id', $profileId)
                    ->where('student_course_enrollments.status', 'active');
            })
            ->selectRaw("
            COUNT(DISTINCT treatments.id) as total,
            COUNT(DISTINCT CASE WHEN treatments.status = 'completed' THEN treatments.id END) as completed,
            COUNT(DISTINCT CASE WHEN treatments.status = 'waiting_instructor_approval' THEN treatments.id END) as pending,
            COUNT(DISTINCT CASE WHEN treatments.status = 'rejected' THEN treatments.id END) as rejected
        ")
            ->first();

        // مقيّد بالكامل عبر استعلامات فرعية مترابطة (Correlated Subqueries)
        // بدل الوصلات (LEFT JOIN) نحو patient_diagnoses/appointments/treatments:
        // كل استعلام فرعي مستقل بذاته ومقيّد بـ a.student_id مباشرة، فلا وجود
        // لأي تمدّد صفوف (Row Fan-out) يمكن أن يسرّب بيانات طالب آخر ضمن
        // التجميع الخارجي. القاعدة الخارجية (enrollments ⋈ case_types ⋈
        // courses) نظيفة أصلاً: صف واحد لكل (طالب، نوع حالة) بلا أي تكرار،
        // فلا حاجة لـ GROUP BY بعد الآن.
        $casesByType = DB::table('student_course_enrollments')
            ->join('case_types', 'student_course_enrollments.course_id', '=', 'case_types.course_id')
            ->join('courses', 'case_types.course_id', '=', 'courses.id')
            ->where('student_course_enrollments.student_id', $profileId)
            ->where('student_course_enrollments.status', 'active')
            ->orderBy('case_types.id')
            ->selectRaw("
            case_types.id as type_id,
            case_types.name as case_type_name,
            courses.name as course_name,
            case_types.required_count as required_to_pass,
            (
                SELECT COUNT(DISTINCT t.id)
                FROM treatments t
                JOIN appointments a ON a.treatment_id = t.id
                JOIN patient_diagnoses pd ON pd.id = t.diagnosis_id
                WHERE a.student_id = ?
                  AND pd.case_type_id = case_types.id
                  AND t.status = 'completed'
            ) as completed_count,
            (
                SELECT COUNT(DISTINCT t.id)
                FROM treatments t
                JOIN appointments a ON a.treatment_id = t.id
                JOIN patient_diagnoses pd ON pd.id = t.diagnosis_id
                WHERE a.student_id = ?
                  AND pd.case_type_id = case_types.id
                  AND t.status IN ('in_progress', 'waiting_instructor_approval')
            ) as in_progress_count
        ", [$userId, $userId])
            ->get();

        $appointmentsCount = DB::table('appointments')
            ->join('patient_diagnoses', 'appointments.diagnosis_id', '=', 'patient_diagnoses.id')
            ->join('case_types', 'patient_diagnoses.case_type_id', '=', 'case_types.id')
            ->join('student_course_enrollments', function ($join) use ($profileId): void {
                $join->on('case_types.course_id', '=', 'student_course_enrollments.course_id')
                    ->where('student_course_enrollments.student_id', $profileId)
                    ->where('student_course_enrollments.status', 'active');
            })
            ->where('appointments.student_id', $userId)
            ->selectRaw("
            COUNT(appointments.id) as total,
            COUNT(CASE WHEN appointments.status = 'attended' THEN 1 END) as attended
        ")
            ->first();

        return [
            'treatments' => $treatmentsCount,
            'types_detail' => $casesByType,
            'appointments' => $appointmentsCount,
        ];
    }

    public function getStudentTreatmentsList(int $userId, string $statusType, int $perPage = 10): LengthAwarePaginator
    {
        $query = Treatment::query()
            ->with([
                'diagnosis.patient:id,full_name',
                'diagnosis.caseType.course:id,name',
                // لعرض بطاقة الحالة بحاجة لتاريخ/فترة آخر أو أقرب موعد مرتبط بها
                // مباشرة، بدل إجبار الفرونت إند على تجميعها من نقاط مواعيد منفصلة.
                'appointments' => fn ($q) => $q->orderBy('appointment_date')->orderBy('slot_number'),
            ])
            ->whereHas('diagnosis.appointments', function ($q) use ($userId): void {
                $q->where('student_id', $userId);
            });

        // rejected تعتبر "منتهية بقرار المعيد" مثل completed تماماً، فمكانها السجل
        // لا قيد المعالجة؛ الحالة تعود فعلياً لـ in_progress (وتظهر بقيد المعالجة
        // من جديد) فقط بعد أن يحجز الطالب موعد متابعة لإعادة الشغل عليها.
        if ($statusType === 'completed') {
            $query->whereIn('status', [
                TreatmentStatus::COMPLETED->value,
                TreatmentStatus::REJECTED->value,
            ]);
        } else {
            $query->whereIn('status', [
                TreatmentStatus::IN_PROGRESS->value,
                TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL->value,
            ]);
        }

        return $query->orderBy('updated_at', 'desc')->paginate($perPage);
    }
}
