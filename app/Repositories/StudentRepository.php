<?php

namespace App\Repositories;

use App\Enums\AppointmentStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\TreatmentStatus;
use App\Models\CaseType;
use App\Models\Course;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;

class StudentRepository
{
    public function getCourseIdsByLevel(int $year, int $semester): array
    {
        return Course::where('year', $year)
            ->where('semester', $semester)
            ->pluck('id')
            ->toArray();
    }

    public function getAvailableCoursesByLevel(int $year, int $semester)
    {
        return Course::with('department')
            ->where(function ($query) use ($year, $semester) {
                $query->where('year', '<', $year)
                    ->orWhere(function ($q) use ($year, $semester) {
                        $q->where('year', $year)
                            ->where('semester', '<=', $semester);
                    });
            })->get();
    }

    public function findExistingEnrollment(int $studentId, int $courseId)
    {
        return StudentCourseEnrollment::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->first();
    }

    public function createEnrollment(array $data)
    {
        return StudentCourseEnrollment::create($data);
    }

    /**
     * جلب مقررات بمعرّفاتها بأعمدة السنة والفصل فقط — تكفي للتحقق من
     * انتماء المقرر لفصل الطالب الحالي دون تحميل الصف كاملاً.
     *
     * @param  array<int, int>  $courseIds
     */
    public function getCoursesByIds(array $courseIds): \Illuminate\Database\Eloquent\Collection
    {
        return Course::query()
            ->select(['id', 'name', 'year', 'semester'])
            ->whereIn('id', $courseIds)
            ->get();
    }

    /**
     * هل للطالب سجل نجاح سابق في هذا المقرر؟
     *
     * نتحقق من سجلات التسجيل التاريخية بحالة COMPLETED، وهي الدليل الوحيد
     * الموثوق على أنه اجتاز المقرر في محاولة سابقة.
     */
    public function hasPassedCourseBefore(int $studentProfileId, int $courseId): bool
    {
        return StudentCourseEnrollment::query()
            ->where('student_id', $studentProfileId)
            ->where('course_id', $courseId)
            ->where('status', EnrollmentStatus::COMPLETED->value)
            ->exists();
    }

    /**
     * هل بدأ الطالب أي نشاط سريري ضمن هذا المقرر؟
     *
     * أي موعد غير ملغى، أو علاج غير ملغى، على نوع حالة يتبع المقرر. نمنع
     * سحب المقرر في هذه الحالة حتى لا تبقى حالات مرتبطة بمقرر غير مسجَّل.
     *
     * استعلام واحد بـ exists وبدون تحميل أي صفوف، فهو يُنفَّذ لكل مقرر يُسحب.
     */
    public function hasClinicalActivityInCourse(int $studentUserId, int $courseId): bool
    {
        return DB::table('appointments')
            ->join('patient_diagnoses', 'appointments.diagnosis_id', '=', 'patient_diagnoses.id')
            ->join('case_types', 'patient_diagnoses.case_type_id', '=', 'case_types.id')
            ->leftJoin('treatments', 'treatments.diagnosis_id', '=', 'patient_diagnoses.id')
            ->where('appointments.student_id', $studentUserId)
            ->where('case_types.course_id', $courseId)
            ->where(function ($query): void {
                $query->where('appointments.status', '!=', AppointmentStatus::CANCELLED->value)
                    ->orWhere(function ($inner): void {
                        $inner->whereNotNull('treatments.id')
                            ->where('treatments.status', '!=', TreatmentStatus::CANCELLED->value);
                    });
            })
            ->exists();
    }

    public function getActiveEnrollmentsForStudent(int $studentId)
    {
        return StudentCourseEnrollment::with(['course.department', 'course.caseTypes'])
            ->where('student_id', $studentId)
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->get();
    }

    public function getCategorizedCaseTypes(int $userId)
    {
        $student = StudentProfile::where('user_id', $userId)->first();
        if (!$student) return ['current' => collect(), 'previous' => collect()];

        $studentYear = (int) $student->academic_year;
        $studentSemester = (int) $student->semester;

        $allEligible = CaseType::join('courses', 'case_types.course_id', '=', 'courses.id')
            ->select('case_types.*', 'courses.year', 'courses.semester')
            ->where(function ($query) use ($studentYear, $studentSemester) {
                $query->where('courses.year', '<', $studentYear)
                    ->orWhere(function ($q) use ($studentYear, $studentSemester) {
                        $q->where('courses.year', '=', $studentYear)
                            ->where('courses.semester', '<=', $studentSemester);
                    });
            })
            ->get();

        return [
            'current' => $allEligible->filter(function ($ct) use ($studentYear, $studentSemester) {
                return $ct->year == $studentYear && $ct->semester == $studentSemester;
            }),
            'previous' => $allEligible->filter(function ($ct) use ($studentYear, $studentSemester) {
                return !($ct->year == $studentYear && $ct->semester == $studentSemester);
            }),
        ];
    }
}
