<?php

namespace App\Services;

use App\Repositories\StudentRepository;
use App\Enums\EnrollmentStatus;
use App\Models\StudentProfile;
use App\Models\StudentCourseEnrollment;
use Illuminate\Support\Collection;
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
        $year = is_object($student->academic_year) ? (int) $student->academic_year->value : (int) $student->academic_year;
        $semester = is_object($student->semester) ? (int) $student->semester->value : (int) $student->semester;

        return $this->studentRepo->getAvailableCaseTypesForStanding($year, $semester);
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
        return DB::transaction(function () use ($student) {

            $year = is_object($student->academic_year) ? (int) $student->academic_year->value : (int) $student->academic_year;
            $semester = is_object($student->semester) ? (int) $student->semester->value : (int) $student->semester;

            $historicalEnrollments = StudentCourseEnrollment::where('student_id', $student->id)->get();

            $completedCourseIds = $historicalEnrollments->where('status', EnrollmentStatus::COMPLETED)->pluck('course_id')->toArray();
            $failedCourseIds    = $historicalEnrollments->where('status', EnrollmentStatus::FAILED)->pluck('course_id')->toArray();

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

            $this->executeDatabaseEnrollment($student->id, $finalCourseIds, $historicalEnrollments);

            return [
                'status'  => 'enrolled_successfully',
                'message' => 'Your courses have been successfully registered and updated based on your current academic standing.',
                'count'   => count($finalCourseIds)
            ];
        });
    }

    /**
     * Logic handling for the first semester.
     */
    private function handleFirstSemesterLogic(int $year, array $completedCourseIds, array $failedCourseIds): array
    {
        $currentSemesterCourses = $this->studentRepo->getCoursesByCurrentLevel($year, 1);
        $currentCourseIds = $currentSemesterCourses->pluck('id')->toArray();

        $isAllCurrentSemesterCompleted = empty(array_diff($currentCourseIds, $completedCourseIds));

        if ($isAllCurrentSemesterCompleted) {
            return [
                'status'     => 'waiting_next_semester',
                'message'    => 'You have already passed all courses for this semester. Please wait until the second semester to begin your clinical practice.',
                'course_ids' => []
            ];
        }

        $coursesToEnroll = [];
        foreach ($currentCourseIds as $id) {
            if (!in_array($id, $completedCourseIds)) {
                $coursesToEnroll[] = $id;
            }
        }

        return [
            'status'     => 'proceed',
            'course_ids' => array_unique(array_merge($coursesToEnroll, $failedCourseIds))
        ];
    }


    private function handleSecondSemesterLogic(int $year, array $completedCourseIds, array $failedCourseIds): array
    {
        $currentSemesterCourses = $this->studentRepo->getCoursesByCurrentLevel($year, 2);
        $currentCourseIds = $currentSemesterCourses->pluck('id')->toArray();

        $coursesToEnroll = [];
        foreach ($currentCourseIds as $id) {
            if (!in_array($id, $completedCourseIds)) {
                $coursesToEnroll[] = $id;
            }
        }

        return array_unique(array_merge($coursesToEnroll, $failedCourseIds));
    }

    private function executeDatabaseEnrollment(int $studentId, array $courseIds, Collection $historicalEnrollments): void
    {
        foreach ($courseIds as $courseId) {
            $existing = $historicalEnrollments->where('course_id', $courseId)->first();

            if ($existing) {
                if ($existing->status === EnrollmentStatus::FAILED) {
                    $existing->update([
                        'status'         => EnrollmentStatus::ACTIVE,
                        'attempts_count' => $existing->attempts_count + 1
                    ]);
                }
            } else {
                StudentCourseEnrollment::create([
                    'student_id'     => $studentId,
                    'course_id'      => $courseId,
                    'status'         => EnrollmentStatus::ACTIVE,
                    'attempts_count' => 1
                ]);
            }
        }
    }
}
