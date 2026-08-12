<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * يسجّل كل طالب في عدد من المقررات المطابقة لسنته الدراسية (التسجيلات الأكاديمية).
 * يجب أن يُشغَّل بعد UserSeeder (لوجود الطلاب) وبعد AcademicSeeder (لوجود المقررات).
 */
class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $coursesByYear = Course::all()->groupBy(
            fn (Course $course): string => (string) $course->year
        );

        if ($coursesByYear->isEmpty()) {
            $this->command?->warn('لا توجد مقررات بعد — شغّل AcademicSeeder أولاً.');

            return;
        }

        $totalStudents = StudentProfile::count();
        $this->command?->getOutput()->progressStart($totalStudents);

        // نستخدم chunkById لتفادي تحميل كل ملفات الطلاب في الذاكرة دفعة واحدة
        StudentProfile::query()->chunkById(100, function (Collection $profiles) use ($coursesByYear): void {
            foreach ($profiles as $profile) {
                $availableCourses = $coursesByYear->get((string) $profile->academic_year);

                if ($availableCourses && $availableCourses->isNotEmpty()) {
                    // نسجّل الطالب في كل مقررات سنته بدل 2-5 عشوائية: مع طالب واحد فقط
                    // للاختبار، التسجيل العشوائي قد يجعل الحالة التي يشخّصها المعيد
                    // غير ظاهرة له لأنها تتبع مقرراً لم يُسجَّل فيه.
                    $selectedCourses = $availableCourses;

                    foreach ($selectedCourses as $course) {
                        StudentCourseEnrollment::factory()->create([
                            'student_id' => $profile->id,
                            'course_id' => $course->id,
                        ]);
                    }
                }

                $this->command?->getOutput()->progressAdvance();
            }
        });

        $this->command?->getOutput()->progressFinish();
    }
}
