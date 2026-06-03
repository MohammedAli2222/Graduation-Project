<?php

namespace App\Repositories;

use App\Enums\EnrollmentStatus;
use App\Models\CaseType;
use App\Models\Course;
use App\Models\StudentCourseEnrollment;

class StudentRepository
{
    /**
     * 1️⃣ جلب المواد المخصصة لفصل محدد وسنة محددة بالظبط
     * (لأننا صرنا بحاجتها بفصل مواد الـ Semester 1 عن الـ Semester 2)
     */
    public function getCoursesByCurrentLevel(int $year, int $semester)
    {
        return Course::where('year', $year)
            ->where('semester', $semester)
            ->get();
    }

    /**
     * 2️⃣ جلب كل المواد المتاحة بناءً على شرط السنة والفصل الأكاديمي (القديمة)
     * تركناها في حال احتجتها بأماكن تانية بالسيستم
     */
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

    /**
     * 3️⃣ البحث عن تسجيل مادة محددة لطالب محدد
     */
    public function findExistingEnrollment(int $studentId, int $courseId)
    {
        return StudentCourseEnrollment::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->first();
    }

    /**
     * 4️⃣ إنشاء سطر تسجيل جديد لأول مرة
     */
    public function createEnrollment(array $data)
    {
        return StudentCourseEnrollment::create($data);
    }


    public function getActiveEnrollmentsForStudent(int $studentId)
    {
        return StudentCourseEnrollment::with(['course.department', 'course.caseTypes'])
            ->where('student_id', $studentId)
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->get();
    }


    public function getAvailableCaseTypesForStanding(int $year, int $semester)
    {
        return CaseType::whereHas('course', function ($query) use ($year, $semester) {
            $query->where('year', $year)
                ->where('semester', $semester);
        })->get();
    }
}
