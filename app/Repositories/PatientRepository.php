<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\AppointmentStatus;
use App\Enums\DiagnosisStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\PatientStatus;
use App\Enums\TreatmentStatus;
use App\Models\CaseType;
use App\Models\Patient;
use App\Models\PatientDiagnose;
use App\Models\StudentCourseEnrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PatientRepository
{
    public function FindOrFail(int $id): Patient
    {
        return Patient::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Patient
    {
        return Patient::create($data);
    }

    /**
     * بحث مرن: يطابق الاسم الكامل أو الرقم المرجعي أو الهاتف جزئياً (LIKE)، لا
     * تطابقاً حرفياً كاملاً كما كانت سابقاً. مُقيَّد بترقيم صفحات (paginate) بدل
     * إرجاع كل النتائج دفعة واحدة، حتى يبقى سريعاً حتى لو تطابقت أسماء كثيرة.
     */
    public function search(string $term): LengthAwarePaginator
    {
        return Patient::with(['media', 'medicalHistory'])
            ->where(function ($query) use ($term): void {
                $query->where('patient_code', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('full_name', 'like', "%{$term}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function findWithMedia(int $id): Patient
    {
        return Patient::with(['media', 'medicalHistory'])->findOrFail($id);
    }

    /**
     * قائمة مرضى الاستقبال، قابلة للفلترة بحالة المريض (availability_status).
     *
     * status = waiting_diagnosis تبقى محتفظة بشرط whereDoesntHave('diagnoses')
     * الأصلي (مرضى لم يُقترَح لهم أي تشخيص بعد إطلاقاً)، أما بقية الحالات
     * (available/fully_reserved/completed) فتُفلتَر بعمود الحالة فقط، إذ لا
     * معنى لشرط "بلا تشخيص" لمريض تجاوز أصلاً مرحلة اقتراح التشخيص.
     */
    public function getReceptionistWaitingList(string $status = 'all'): LengthAwarePaginator
    {
        $validStatuses = array_map(fn ($case) => $case->value, PatientStatus::cases());
        $status = in_array($status, $validStatuses, true) ? $status : 'all';

        $query = Patient::with(['media', 'medicalHistory'])->orderBy('created_at', 'asc');

        if ($status === PatientStatus::WAITING_DIAGNOSIS->value) {
            $query->where('availability_status', $status)->whereDoesntHave('diagnoses');
        } elseif ($status !== 'all') {
            $query->where('availability_status', $status);
        }

        return $query->paginate(10);
    }

    // =====================================================================
    // Endpoint A — المرضى بانتظار وضع خطة تشخيص من المعيد
    // =====================================================================

    /**
     * قائمة موحّدة للمرضى الذين ينتظرون قرار المعيد على مستوى *التشخيص*،
     * من مصدرَين مختلفين تماماً:
     *
     * - reception: مريض سجّله موظف الاستقبال ولا يحمل أي تشخيص بعد، فالمعيد
     *   هو من يضع له خطة الحالات (لا علاقة له بمجموعة طلابية).
     * - student: تشخيص اقترحه طالب من مجموعة يشرف عليها هذا المعيد وينتظر
     *   الاعتماد أو الرفض.
     *
     * سبب الدمج في استعلام واحد: الشاشة تعرضهما في قائمة واحدة مرتّبة زمنياً،
     * ولو جُلبا باستعلامين لتعذّر الترقيم (pagination) الصحيح بينهما.
     *
     * ملاحظة مهمة: هذه القائمة لا تحوي أبداً حالات نُفِّذ علاجها وتنتظر
     * التقييم — تلك مسؤولية Endpoint B حصراً.
     *
     * @param  'all'|'reception'|'student'  $source
     */
    public function getPendingDiagnosisPatients(
        int $instructorProfileId,
        string $source = 'all',
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = Patient::query()
            ->select(['id', 'patient_code', 'full_name', 'gender', 'phone', 'birth_date', 'address', 'preliminary_diagnosis', 'availability_status', 'added_by', 'created_at'])
            ->with([
                'media',
                'medicalHistory',
                // نحمّل فقط التشخيصات المعلّقة التابعة لطلاب هذا المعيد؛ أما
                // مرضى الاستقبال فتأتي علاقتهم فارغة وهذا هو المتوقع.
                'diagnoses' => function ($relation) use ($instructorProfileId): void {
                    $relation->where('patient_diagnoses.status', DiagnosisStatus::WAITING_APPROVAL->value)
                        ->whereExists(fn ($sub) => $this->supervisedStudentExists($sub, $instructorProfileId))
                        // media/department/caseType كلها يقرأها PatientResource؛
                        // بدون تحميلها هنا ينطلق استعلام لكل تشخيص (N+1).
                        ->with([
                            'caseType:id,name,course_id',
                            'department:id,name',
                            'student:id,first_name,last_name',
                            'media',
                        ]);
                },
            ]);

        match ($source) {
            'reception' => $query->where(fn (Builder $q) => $this->scopeReceptionSource($q)),
            'student' => $query->where(fn (Builder $q) => $this->scopeStudentSource($q, $instructorProfileId)),
            default => $query->where(function (Builder $q) use ($instructorProfileId): void {
                $q->where(fn (Builder $inner) => $this->scopeReceptionSource($inner))
                    ->orWhere(fn (Builder $inner) => $this->scopeStudentSource($inner, $instructorProfileId));
            }),
        };

        return $query->orderBy('created_at', 'asc')->paginate($perPage);
    }

    /**
     * مرضى الاستقبال: بانتظار التشخيص وبلا أي تشخيص مسجّل بعد.
     */
    private function scopeReceptionSource(Builder $query): Builder
    {
        return $query
            ->where('availability_status', PatientStatus::WAITING_DIAGNOSIS->value)
            ->whereDoesntHave('diagnoses');
    }

    /**
     * مرضى باقتراح طالب: يملكون تشخيصاً بانتظار الاعتماد مقترحاً من طالب
     * ينتمي لمجموعة يشرف عليها هذا المعيد.
     */
    private function scopeStudentSource(Builder $query, int $instructorProfileId): Builder
    {
        return $query->whereHas('diagnoses', function ($diagnosisQuery) use ($instructorProfileId): void {
            $diagnosisQuery
                ->where('patient_diagnoses.status', DiagnosisStatus::WAITING_APPROVAL->value)
                ->whereExists(fn ($sub) => $this->supervisedStudentExists($sub, $instructorProfileId));
        });
    }

    /**
     * شرط الإشراف: الطالب مقترِح التشخيص ينتمي لمجموعة مرتبطة بهذا المعيد.
     *
     * نستعمل whereExists مع joins مباشرة بدل سلسلة whereHas المتداخلة
     * (student → studentProfile → group → instructors) التي كانت تولّد أربعة
     * استعلامات فرعية متشعّبة لكل صف.
     *
     * @param  \Illuminate\Database\Query\Builder  $sub
     */
    private function supervisedStudentExists($sub, int $instructorProfileId)
    {
        return $sub->select(DB::raw(1))
            ->from('student_profiles')
            ->join('group_instructor', 'group_instructor.group_id', '=', 'student_profiles.group_id')
            ->whereColumn('student_profiles.user_id', 'patient_diagnoses.suggested_by_student_id')
            ->where('group_instructor.instructor_profile_id', $instructorProfileId);
    }

    /**
     * @deprecated استُبدلت بـ getPendingDiagnosisPatients(..., source: 'student').
     * أُبقيت لتوافق النقطة القديمة /instructor/patients/student-pending.
     */
    public function getStudentPendingRequests(int $instructorProfileId): LengthAwarePaginator
    {
        return $this->getPendingDiagnosisPatients($instructorProfileId, 'student');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Patient
    {
        $patient = Patient::findOrFail($id);
        $patient->update($data);

        return $patient;
    }

    public function getReceptionistStatsByStatus(int $receptionistId, ?string $status = null): int
    {
        $query = Patient::where('added_by', $receptionistId)
            ->whereDate('created_at', now()->today());

        if ($status) {
            $query->where('availability_status', $status);
        }

        return $query->count();
    }

    public function updateAvailability(int $patientId, string $status): bool
    {
        return (bool) Patient::where('id', $patientId)->update(['availability_status' => $status]);
    }

    public function getByCaseTypeAndStatus(int $caseTypeId, string $statusValue): LengthAwarePaginator
    {
        return PatientDiagnose::select(
            'id',
            'patient_id',
            'case_type_id',
            'department_id',
            'instructor_id',
            'status',
            'estimated_cost',
            'final_diagnosis',
            'created_at'
        )
            ->with([
                'patient' => function ($query): void {
                    $query->select('id', 'full_name', 'phone', 'gender', 'birth_date', 'address');
                },
                'caseType' => function ($query): void {
                    $query->select('id', 'name');
                },
                'department' => function ($query): void {
                    $query->select('id', 'name');
                },
                'instructor' => function ($query): void {
                    $query->select('id', 'first_name', 'last_name');
                },
            ])
            ->where('case_type_id', $caseTypeId)
            ->where('status', $statusValue)
            ->latest()
            ->paginate(15);
    }

    public function checkIfCaseBelongsToActiveCourse(int $caseTypeId, int $studentId): bool
    {
        return StudentCourseEnrollment::where('student_id', $studentId)
            ->where('status', EnrollmentStatus::ACTIVE)
            ->whereHas('course.caseTypes', function ($query) use ($caseTypeId): void {
                $query->where('id', $caseTypeId);
            })
            ->exists();
    }

    /**
     * هل استنفد الطالب حصته (required_count) من نوع الحالة هذا؟ نفس تعريف
     * "المُنجز" المعتمد بلوحة تقدّم الطالب (TreatmentRepository::getProgressStats):
     * فقط العلاجات completed تُحتسب فعلياً ضمن المتطلب. بالإضافة لذلك، أي حجز
     * لم يُحسم بعد (محجوز بلا علاج، أو علاج قيد التنفيذ/بانتظار اعتماد المعيد)
     * يُحتسب أيضاً لمنع حجز عدة مرضى من نفس النوع دفعة واحدة قبل إنجاز أيٍّ
     * منها. المواعيد الملغاة والعلاجات المرفوضة/الملغاة لا تُحتسب أبداً، لأن
     * تلك الحالات تُعاد إتاحتها للطالب نفسه من جديد.
     */
    public function hasReachedCaseTypeQuota(int $caseTypeId, int $studentUserId): bool
    {
        $requiredCount = CaseType::find($caseTypeId)?->required_count;

        if (! $requiredCount) {
            return false;
        }

        $activeClaimsCount = DB::table('appointments')
            ->join('patient_diagnoses', 'appointments.diagnosis_id', '=', 'patient_diagnoses.id')
            ->leftJoin('treatments', 'appointments.treatment_id', '=', 'treatments.id')
            ->where('appointments.student_id', $studentUserId)
            ->where('patient_diagnoses.case_type_id', $caseTypeId)
            ->where('appointments.status', '!=', AppointmentStatus::CANCELLED->value)
            ->where(function ($query): void {
                $query->whereNull('treatments.id')
                    ->orWhereIn('treatments.status', [
                        TreatmentStatus::IN_PROGRESS->value,
                        TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL->value,
                        TreatmentStatus::COMPLETED->value,
                    ]);
            })
            ->distinct()
            ->count('patient_diagnoses.id');

        return $activeClaimsCount >= $requiredCount;
    }

    public function getDiagnosisDetailsWithPatientMedia(int $id): PatientDiagnose
    {
        return PatientDiagnose::with([
            'patient' => fn ($q) => $q->select('id', 'full_name', 'phone', 'gender', 'birth_date', 'address'),
            'patient.media',
            'patient.medicalHistory',
            // وسائط التشخيص نفسه: يقرأها PatientDiagnosisDetailsResource إلى جانب
            // وسائط المريض، وبدون تحميلها مسبقاً ينطلق استعلام إضافي عند بناء الرد
            'media',
            'caseType' => fn ($q) => $q->select('id', 'name'),
            'department' => fn ($q) => $q->select('id', 'name'),
            'instructor' => fn ($q) => $q->select('id', 'first_name', 'last_name'),
        ])
            ->where(function ($query) use ($id): void {
                $query->where('id', $id)
                    ->where(function ($q): void {
                        $q->where('status', DiagnosisStatus::AVAILABLE->value)
                            ->orWhereHas('appointments', function ($appointmentQuery): void {
                                $appointmentQuery->where('student_id', auth()->id());
                            });
                    });
            })
            ->findOrFail($id);
    }

    public function getAvailableDiagnosesForPatient(int $patientId, int $studentId): Collection
    {
        $activeCourseIds = StudentCourseEnrollment::where('student_id', $studentId)
            ->where('status', EnrollmentStatus::ACTIVE)
            ->pluck('course_id');

        return PatientDiagnose::where('patient_id', $patientId)
            ->whereHas('caseType', function ($query) use ($activeCourseIds): void {
                $query->whereIn('course_id', $activeCourseIds);
            })
            ->with(['caseType', 'department', 'instructor'])
            ->latest()
            ->get();
    }
}
