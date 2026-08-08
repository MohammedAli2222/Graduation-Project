<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $year = $this->faker->numberBetween(1, 5);
        $semester = $this->faker->numberBetween(1, 2);

        return [
            'name' => $this->faker->words(3, true) . ' ' . $year,
            'department_id' => Department::factory(),
            'year' => $year,
            'semester' => $semester,
        ];
    }
}
