<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentProfile;
use App\Repositories\StudentRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StudentCourseService
{
    protected StudentRepository $studentRepo;

    public function __construct(StudentRepository $studentRepo)
    {
        $this->studentRepo = $studentRepo;
    }

    public function getCaseTypesForDropdown(StudentProfile $student): array
    {
        // إنشاء مفتاح التخزين المؤقت بناءً على معرف الطالب
        $cacheKey = "case_types:student_{$student->id}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($student): array {
            return $this->studentRepo->getCategorizedCaseTypes($student->user_id);
        });
    }

    public function getActiveCoursesForStudent(int $studentId): \Illuminate\Support\Collection
    {
        // جلب المقررات الفعالة للطالب
        $enrollments = $this->studentRepo->getActiveEnrollmentsForStudent($studentId);

        return $enrollments->map(function (StudentCourseEnrollment $enrollment) {
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

        $historicalEnrollments = StudentCourseEnrollment::with('course:id,semester')
            ->where('student_id', $student->id)
            ->select('id', 'student_id', 'course_id', 'status', 'attempts_count')
            ->get();

        $completedCourseIds = $historicalEnrollments->where('status', EnrollmentStatus::COMPLETED)->pluck('course_id')->toArray();

        $failedCourseIds = $historicalEnrollments->filter(function (StudentCourseEnrollment $enrollment) use ($semester): bool {
            return $enrollment->status === EnrollmentStatus::FAILED
                && $enrollment->course !== null
                && (int) $enrollment->course->semester === $semester;
        })->pluck('course_id')->toArray();

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

        DB::transaction(function () use ($student, $finalCourseIds, $historicalEnrollments): void {
            $this->executeDatabaseEnrollment($student->id, $finalCourseIds, $historicalEnrollments);
        });

        return [
            'status' => 'enrolled_successfully',
            'message' => 'Your courses have been successfully registered and updated based on your current academic standing.',
            'count' => count($finalCourseIds),
        ];
    }

    public function manualEnrollCourses(StudentProfile $student, array $courseIds): array
    {
        return DB::transaction(function () use ($student, $courseIds): array {

            $historicalEnrollments = StudentCourseEnrollment::where('student_id', $student->id)->get();

            $enrolledCourseIds = [];
            $skippedCourseIds = [];

            foreach ($courseIds as $courseId) {
                $existing = $historicalEnrollments->firstWhere('course_id', $courseId);

                if ($existing) {
                    if ($existing->status === EnrollmentStatus::COMPLETED) {
                        throw new Exception("Cannot re-enroll in course #{$courseId}: you have already passed this course.", 422);
                    }

                    if ($existing->status === EnrollmentStatus::ACTIVE) {
                        $skippedCourseIds[] = (int) $courseId;
                        continue;
                    }

                    $existing->update([
                        'status' => EnrollmentStatus::ACTIVE,
                        'attempts_count' => $existing->attempts_count + 1,
                    ]);

                    $enrolledCourseIds[] = (int) $courseId;
                    continue;
                }

                StudentCourseEnrollment::create([
                    'student_id' => $student->id,
                    'course_id' => $courseId,
                    'status' => EnrollmentStatus::ACTIVE,
                    'attempts_count' => 1,
                ]);

                $enrolledCourseIds[] = (int) $courseId;
            }

            return [
                'enrolled_course_ids' => $enrolledCourseIds,
                'skipped_course_ids' => $skippedCourseIds,
                'enrolled_count' => count($enrolledCourseIds),
            ];
        });
    }

    private function handleFirstSemesterLogic(int $year, array $completedCourseIds, array $failedCourseIds): array
    {
        $currentCourseIds = $this->studentRepo->getCourseIdsByLevel($year, 1);

        $newCourses = array_diff($currentCourseIds, $completedCourseIds, $failedCourseIds);

        $coursesToEnroll = array_values(array_unique(array_merge($newCourses, $failedCourseIds)));

        if (empty($coursesToEnroll)) {
            return [
                'status' => 'waiting_next_semester',
                'message' => 'You have already passed all courses for this semester. Please wait until the second semester to begin your clinical practice.',
                'course_ids' => [],
            ];
        }

        return [
            'status' => 'proceed',
            'course_ids' => $coursesToEnroll,
        ];
    }

    private function handleSecondSemesterLogic(int $year, array $completedCourseIds, array $failedCourseIds): array
    {
        $currentCourseIds = $this->studentRepo->getCourseIdsByLevel($year, 2);

        $newCourses = array_diff($currentCourseIds, $completedCourseIds, $failedCourseIds);

        return array_values(array_unique(array_merge($newCourses, $failedCourseIds)));
    }

    private function executeDatabaseEnrollment(int $studentId, array $courseIds, \Illuminate\Database\Eloquent\Collection $historicalEnrollments): void
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
                    'status' => EnrollmentStatus::ACTIVE->value,
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
