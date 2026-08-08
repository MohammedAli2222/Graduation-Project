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
            ->whereIn('status', [
                TreatmentStatus::COMPLETED->value,
                TreatmentStatus::IN_PROGRESS->value,
                TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL->value,
            ])
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
    public function getPendingApprovalsListForInstructor(int $instructorProfileId, int $perPage = 10): LengthAwarePaginator
    {
        return Treatment::query()
            ->where('treatments.status', TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL->value)
            ->whereExists(function ($query) use ($instructorProfileId): void {
                $query->select(DB::raw(1))
                    ->from('appointments')
                    ->join('student_profiles', 'student_profiles.user_id', '=', 'appointments.student_id')
                    ->join('group_instructor', 'group_instructor.group_id', '=', 'student_profiles.group_id')
                    ->whereColumn('appointments.diagnosis_id', 'treatments.diagnosis_id')
                    ->where('group_instructor.instructor_profile_id', $instructorProfileId);
            })
            ->with([
                'diagnosis:id,patient_id,case_type_id',
                'diagnosis.caseType:id,name',
                'diagnosis.appointments' => function ($query): void {
                    $query->select('id', 'diagnosis_id', 'student_id', 'patient_id')
                        ->orderBy('id');
                },
                'diagnosis.appointments.student:id,first_name,last_name',
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

        $treatmentsCount = DB::table('treatments')
            ->join('patient_diagnoses', 'treatments.diagnosis_id', '=', 'patient_diagnoses.id')
            ->join('case_types', 'patient_diagnoses.case_type_id', '=', 'case_types.id')
            ->join('student_course_enrollments', function ($join) use ($profileId): void {
                $join->on('case_types.course_id', '=', 'student_course_enrollments.course_id')
                    ->where('student_course_enrollments.student_id', $profileId)
                    ->where('student_course_enrollments.status', 'active');
            })
            ->join('appointments', 'patient_diagnoses.id', '=', 'appointments.diagnosis_id')
            ->where('appointments.student_id', $userId)
            ->selectRaw("
            COUNT(DISTINCT treatments.id) as total,
            COUNT(DISTINCT CASE WHEN treatments.status = 'completed' THEN treatments.id END) as completed,
            COUNT(DISTINCT CASE WHEN treatments.status = 'waiting_instructor_approval' THEN treatments.id END) as pending,
            COUNT(DISTINCT CASE WHEN treatments.status = 'rejected' THEN treatments.id END) as rejected
        ")
            ->first();

        $casesByType = DB::table('student_course_enrollments')
            ->join('case_types', 'student_course_enrollments.course_id', '=', 'case_types.course_id')
            ->join('courses', 'case_types.course_id', '=', 'courses.id')
            ->leftJoin('patient_diagnoses', 'case_types.id', '=', 'patient_diagnoses.case_type_id')
            ->leftJoin('treatments', 'patient_diagnoses.id', '=', 'treatments.diagnosis_id')
            ->leftJoin('appointments', function ($join) use ($userId): void {
                $join->on('patient_diagnoses.id', '=', 'appointments.diagnosis_id')
                    ->where('appointments.student_id', $userId);
            })
            ->where('student_course_enrollments.student_id', $profileId)
            ->where('student_course_enrollments.status', 'active')
            ->groupBy('case_types.id', 'case_types.name', 'case_types.required_count', 'courses.name')
            ->selectRaw("
            case_types.id as type_id,
            case_types.name as case_type_name,
            courses.name as course_name,
            case_types.required_count as required_to_pass,
            COUNT(DISTINCT CASE WHEN treatments.status = 'completed' THEN treatments.id END) as completed_count,
            COUNT(DISTINCT CASE WHEN treatments.status IN ('in_progress','waiting_instructor_approval') THEN treatments.id END) as in_progress_count
        ")
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
            ])
            ->whereHas('diagnosis.appointments', function ($q) use ($userId): void {
                $q->where('student_id', $userId);
            });

        if ($statusType === 'completed') {
            $query->where('status', TreatmentStatus::COMPLETED->value);
        } else {
            $query->whereIn('status', [
                TreatmentStatus::IN_PROGRESS->value,
                TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL->value,
                TreatmentStatus::REJECTED->value,
            ]);
        }

        return $query->orderBy('updated_at', 'desc')->paginate($perPage);
    }
}
