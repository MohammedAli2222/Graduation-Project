<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'group_id' => Group::factory(),
            'phone' => '09' . fake()->numerify('#######'),
            'exam_number' => fake()->unique()->numerify('2024#####'),
            'university' => 'جامعة دمشق - كلية طب الأسنان',
            'academic_year' => fake()->randomElement([4, 5]),
            'semester' => fake()->randomElement([1, 2]),
        ];
    }
}
