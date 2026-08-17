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

    /**
     * قائمة إعداد المقررات: كل المقررات المؤهَّلة أكاديمياً للطالب (فصله
     * الحالي + كل ما سبقه)، مقسّمة إلى فصل حالي/فصول سابقة، وكل مقرر معه
     * أعلام حالة (is_enrolled/can_enroll/can_drop/lock_reason) جاهزة للعرض.
     *
     * @return array{current_semester_courses: \Illuminate\Support\Collection, previous_semesters_courses: \Illuminate\Support\Collection}
     */
    public function getCourseSetupList(StudentProfile $student): array
    {
        $year = $this->normalizeLevel($student->academic_year);
        $semester = $this->normalizeLevel($student->semester);

        $courses = $this->studentRepo->getAvailableCoursesByLevel($year, $semester);
        $enrollmentsByCourseId = $this->studentRepo->getEnrollmentsKeyedByCourse($student->id);

        $current = collect();
        $previous = collect();

        foreach ($courses as $course) {
            $enrollment = $enrollmentsByCourseId->get($course->id);
            $status = $enrollment?->status;

            $isEnrolled = $status === EnrollmentStatus::ACTIVE;
            $hasPassedBefore = $status === EnrollmentStatus::COMPLETED;

            // لا داعي لاستعلام النشاط السريري إلا للمقررات المسجَّلة فعلياً،
            // فيبقى عدد الاستعلامات محصوراً بعدد مقررات الطالب النشطة فقط.
            $hasClinicalActivity = $isEnrolled
                && $this->studentRepo->hasClinicalActivityInCourse((int) $student->user_id, $course->id);

            $course->is_enrolled = $isEnrolled;
            $course->can_enroll = ! $isEnrolled && ! $hasPassedBefore;
            $course->can_drop = $isEnrolled && ! $hasClinicalActivity;
            $course->lock_reason = match (true) {
                $hasPassedBefore => 'لقد اجتزت هذا المقرر سابقاً.',
                $hasClinicalActivity => 'لا يمكن سحب المقرر بعد بدء نشاط سريري فيه.',
                default => null,
            };

            if ((int) $course->year === $year && (int) $course->semester === $semester) {
                $current->push($course);
            } else {
                $previous->push($course);
            }
        }

        return [
            'current_semester_courses' => $current->values(),
            'previous_semesters_courses' => $previous->values(),
        ];
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

    /**
     * إضافة مقررات يدوياً إلى تسجيل الطالب الحالي.
     *
     * القيد رقم ١ (قواعد الفصل): يُسمح بإضافة مقررات فصل الطالب الدراسي
     * الحالي أو أي فصل سابق (مقررات محمولة/راسب فيها الطالب سابقاً)، ولا
     * يُسمح بإضافة مقرر من فصل مستقبلي لم يصل إليه الطالب بعد.
     *
     * @param  array<int, int|string>  $courseIds
     * @return array{enrolled_course_ids: array<int, int>, skipped_course_ids: array<int, int>, enrolled_count: int}
     */
    public function manualEnrollCourses(StudentProfile $student, array $courseIds): array
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));

        $this->assertCoursesAreNotInTheFuture($student, $courseIds);

        $result = DB::transaction(function () use ($student, $courseIds): array {

            $historicalEnrollments = StudentCourseEnrollment::where('student_id', $student->id)
                ->lockForUpdate()
                ->get();

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

                    // إعادة تفعيل تسجيل سابق: الرسوب محاولة جديدة فيزيد
                    // العدّاد، أما الانسحاب فليس محاولة ولا يجوز أن يعاقب عليه.
                    $existing->update([
                        'status' => EnrollmentStatus::ACTIVE,
                        'attempts_count' => $existing->status === EnrollmentStatus::FAILED
                            ? $existing->attempts_count + 1
                            : max(1, (int) $existing->attempts_count),
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

        $this->flushStudentCaches($student);

        return $result;
    }

    /**
     * سحب مقررات من التسجيل الفعّال للطالب مع تطبيق حواجز الحماية (Guardrails).
     *
     * السلوك: نجاح جزئي — كل مقرر يُعالَج على حدة، وما يتعذّر سحبه يعود ضمن
     * failed_to_drop مع سبب واضح بدل إفشال الطلب كاملاً. لا نرمي استثناءً إلا
     * إذا لم يُسحب أي مقرر إطلاقاً.
     *
     * @param  array<int, int|string>  $courseIds
     * @return array{dropped_courses: array<int, int>, failed_to_drop: array<int|string, string>}
     */
    public function dropCourses(StudentProfile $student, array $courseIds): array
    {
        $result = DB::transaction(function () use ($student, $courseIds): array {
            $droppedCourseIds = [];
            $failedToDropIds = [];

            $activeEnrollments = StudentCourseEnrollment::query()
                ->where('student_id', $student->id)
                ->whereIn('course_id', $courseIds)
                ->where('status', EnrollmentStatus::ACTIVE->value)
                ->get()
                ->keyBy('course_id');

            foreach ($courseIds as $courseId) {
                $enrollment = $activeEnrollments->get($courseId);

                if (! $enrollment) {
                    $failedToDropIds[$courseId] = 'Course is not currently active.';
                    continue;
                }

                // حماية معمارية: منع سحب المقرر إذا كان الطالب قد بدأ بأي نشاط سريري يتبعه
                $hasClinicalActivity = $this->studentRepo->hasClinicalActivityInCourse($student->user_id, $courseId);

                if ($hasClinicalActivity) {
                    $failedToDropIds[$courseId] = 'لا يمكن سحب المقرر بعد بدء نشاط سريري فيه.';
                    continue;
                }

                // التحقق مما إذا كان الطالب قد اجتاز المقرر بنجاح في سنة سابقة
                $hasPassedBefore = $this->studentRepo->hasPassedCourseBefore($student->id, $courseId);

                if ($hasPassedBefore) {
                    // إذا كان ناجحاً فيه سابقاً، نقوم بإلغاء التسجيل النشط فقط دون مسح السجل التاريخي الناجح
                    $enrollment->delete();
                } else {
                    // إذا كان مقرراً جديداً ولم ينجح فيه سابقاً، نحوله إلى مسحوب
                    $enrollment->update(['status' => EnrollmentStatus::DROPPED->value]);
                }

                $droppedCourseIds[] = (int) $courseId;
            }

            if (empty($droppedCourseIds) && ! empty($failedToDropIds)) {
                throw new Exception('Failed to drop any courses due to existing clinical activities or invalid states.', 422);
            }

            return [
                'dropped_courses' => $droppedCourseIds,
                'failed_to_drop' => $failedToDropIds,
            ];
        });

        // سحب المقرر يغيّر أنواع الحالات المتاحة وإحصائيات التقدّم، فبدون
        // تصفير الكاش يبقى الطالب يرى مقرراته القديمة حتى انتهاء مدة الكاش.
        $this->flushStudentCaches($student);

        return $result;
    }

    /**
     * التحقق من أن كل المقررات المطلوبة تعود لفصل الطالب الحالي أو ما قبله؛
     * يُرفض فقط أي مقرر يتبع فصلاً مستقبلياً لم يصل إليه الطالب بعد.
     *
     * @param  array<int, int>  $courseIds
     */
    private function assertCoursesAreNotInTheFuture(StudentProfile $student, array $courseIds): void
    {
        if ($courseIds === []) {
            throw new Exception('No courses provided to enroll.', 422);
        }

        $year = $this->normalizeLevel($student->academic_year);
        $semester = $this->normalizeLevel($student->semester);

        $courses = $this->studentRepo->getCoursesByIds($courseIds);

        if ($courses->count() !== count($courseIds)) {
            throw new Exception('One or more selected courses do not exist.', 404);
        }

        $future = $courses->filter(function ($course) use ($year, $semester): bool {
            $courseYear = (int) $course->year;
            $courseSemester = (int) $course->semester;

            return $courseYear > $year || ($courseYear === $year && $courseSemester > $semester);
        });

        if ($future->isNotEmpty()) {
            throw new Exception(
                sprintf(
                    'You can only enroll in courses from your current semester (year %d, semester %d) or earlier. Rejected: %s.',
                    $year,
                    $semester,
                    $future->pluck('name')->implode(', ')
                ),
                422
            );
        }
    }

    /**
     * academic_year و semester قد يكونان Enum أو رقماً حسب مصدر البيانات.
     */
    private function normalizeLevel(mixed $value): int
    {
        return is_object($value) ? (int) $value->value : (int) $value;
    }

    /**
     * أي تعديل على التسجيل يغيّر أنواع الحالات المتاحة وإحصائيات التقدّم،
     * فنُبطل الكاشين معاً وإلا بقي الطالب يرى مقررات وحالات قديمة ليوم كامل.
     */
    private function flushStudentCaches(StudentProfile $student): void
    {
        Cache::forget("case_types:student_{$student->id}");
        Cache::forget("student_progress_stats_{$student->user_id}");
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
