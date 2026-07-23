<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends Factory<StudentCourseEnrollment>
 */
class StudentCourseEnrollmentFactory extends Factory
{
    protected $model = StudentCourseEnrollment::class;

    public function definition(): array
    {
        return [
            'student_id' => StudentProfile::factory(),
            'course_id' => Course::query()->inRandomOrder()->value('id')
                ?? throw new RuntimeException('Seed courses before generating enrollments.'),
            'status' => $this->faker->randomElement($this->weightedStatuses()),
            'attempts_count' => $this->faker->numberBetween(1, 3),
        ];
    }

    /**
     * @return array<int, EnrollmentStatus>
     */
    private function weightedStatuses(): array
    {
        return [
            ...array_fill(0, 55, EnrollmentStatus::ACTIVE),
            ...array_fill(0, 30, EnrollmentStatus::COMPLETED),
            ...array_fill(0, 10, EnrollmentStatus::FAILED),
            ...array_fill(0, 5, EnrollmentStatus::DROPPED),
        ];
    }
}
