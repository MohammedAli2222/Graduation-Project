<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{


    private const TOTAL_STUDENTS = 500;

    /**
     * Seed university dental student accounts (with student profiles and the
     * "student" role), then enroll each into a handful of real courses that
     * match their academic year.
     */
    public function run(): void
    {
        User::factory()->count(self::TOTAL_STUDENTS)->student()->create();

        $coursesByYear = Course::all()->groupBy(
            fn (Course $course): string => (string) $course->year
        );

        if ($coursesByYear->isEmpty()) {
            $this->command?->warn('No courses found — run DepartmentAndCourseSeeder before StudentSeeder.');

            return;
        }

        StudentProfile::query()->chunkById(100, function (Collection $profiles) use ($coursesByYear): void {
            foreach ($profiles as $profile) {
                $availableCourses = $coursesByYear->get((string) $profile->academic_year);

                if (! $availableCourses || $availableCourses->isEmpty()) {
                    continue;
                }

                $selectedCourses = $availableCourses->random(
                    min($availableCourses->count(), random_int(2, 5))
                );

                foreach ($selectedCourses as $course) {
                    StudentCourseEnrollment::factory()->create([
                        'student_id' => $profile->id,
                        'course_id' => $course->id,
                    ]);
                }
            }
        });
    }
}
