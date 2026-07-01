<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentProfile;
use App\Repositories\StudentRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StudentCourseService
{
    protected StudentRepository $studentRepo;

    public function __construct(StudentRepository $studentRepo)
    {
        $this->studentRepo = $studentRepo;
    }

    public function getCaseTypesForDropdown(StudentProfile $student)
    {

        $cacheKey = "case_types:student_{$student->id}";


        return Cache::remember($cacheKey, now()->addDay(), function () use ($student) {
            return $this->studentRepo->getCategorizedCaseTypes($student->user_id);
        });
    }

    public function getActiveCoursesForStudent(int $studentId)
    {
        $enrollments = $this->studentRepo->getActiveEnrollmentsForStudent($studentId);

        return $enrollments->map(function ($enrollment) {
            $course = $enrollment->course;
            if ($course) {
                $course->attempts_count = $enrollment->attempts_count;
                $course->enrollment_status = $enrollment->status;
            }

            return $course;
        })->filter();
    }

    public function autoEnrollStudentCourses(StudentProfile $student): array
    {
        $year = is_object($student->academic_year) ? (int) $student->academic_year->value : (int) $student->academic_year;
        $semester = is_object($student->semester) ? (int) $student->semester->value : (int) $student->semester;

        $historicalEnrollments = StudentCourseEnrollment::where('student_id', $student->id)
            ->select('id', 'student_id', 'course_id', 'status', 'attempts_count')
            ->get();

        $completedCourseIds = $historicalEnrollments->where('status', EnrollmentStatus::COMPLETED)->pluck('course_id')->toArray();
        $failedCourseIds = $historicalEnrollments->where('status', EnrollmentStatus::FAILED)->pluck('course_id')->toArray();

        $finalCourseIds = [];

        if ($semester === 1) {
            $result = $this->handleFirstSemesterLogic($year, $completedCourseIds, $failedCourseIds);
            if ($result['status'] === 'waiting_next_semester') {
                return $result;
            }
            $finalCourseIds = $result['course_ids'];
        } else {
            $finalCourseIds = $this->handleSecondSemesterLogic($year, $completedCourseIds, $failedCourseIds);
        }

        DB::transaction(function () use ($student, $finalCourseIds, $historicalEnrollments) {
            $this->executeDatabaseEnrollment($student->id, $finalCourseIds, $historicalEnrollments);
        });

        return [
            'status' => 'enrolled_successfully',
            'message' => 'Your courses have been successfully registered and updated based on your current academic standing.',
            'count' => count($finalCourseIds),
        ];
    }

    /**
     * Logic handling for the first semester.
     */
    private function handleFirstSemesterLogic(int $year, array $completedCourseIds, array $failedCourseIds): array
    {
        // ⚡ جلب الـ IDs فقط مباشرة من الـ Repo
        $currentCourseIds = $this->studentRepo->getCourseIdsByLevel($year, 1);

        $newCourses = array_diff($currentCourseIds, $completedCourseIds, $failedCourseIds);

        $coursesToEnroll = array_unique(array_merge($newCourses, $failedCourseIds));


        if (empty($coursesToEnroll)) {
            return [
                'status' => 'waiting_next_semester',
                'message' => 'You have already passed all courses for this semester. Please wait until the second semester to begin your clinical practice.',
                'course_ids' => [],
            ];
        }

        // تحسين الـ Clean Code: استبدال Loop التقليدي بـ array_diff لمعالجة أسرع في الذاكرة
        //$coursesToEnroll = array_diff($currentCourseIds, $completedCourseIds);

        return [
            'status' => 'proceed',
            'course_ids' => $coursesToEnroll,
        ];
    }

    private function handleSecondSemesterLogic(int $year, array $completedCourseIds, array $failedCourseIds): array
    {
        $currentCourseIds = $this->studentRepo->getCourseIdsByLevel($year, 2);
        $coursesToEnroll = array_diff($currentCourseIds, $completedCourseIds);

        return array_unique(array_merge($coursesToEnroll, $failedCourseIds));
    }

    private function executeDatabaseEnrollment(int $studentId, array $courseIds, $historicalEnrollments): void
    {
        $now = Carbon::now();
        $inserts = [];

        foreach ($courseIds as $courseId) {
            $existing = $historicalEnrollments->where('course_id', $courseId)->first();

            if ($existing) {
                if ($existing->status === EnrollmentStatus::FAILED) {
                    $existing->update([
                        'status' => EnrollmentStatus::ACTIVE,
                        'attempts_count' => $existing->attempts_count + 1,
                    ]);
                }
            } else {
                $inserts[] = [
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'status' => EnrollmentStatus::ACTIVE,
                    'attempts_count' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($inserts)) {
            StudentCourseEnrollment::insert($inserts);
        }
    }
}
