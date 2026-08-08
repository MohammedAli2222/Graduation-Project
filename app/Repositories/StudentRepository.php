<?php

namespace App\Repositories;

use App\Enums\EnrollmentStatus;
use App\Models\CaseType;
use App\Models\Course;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentProfile;

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
