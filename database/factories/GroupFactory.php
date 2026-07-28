<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        // توليد أسماء مجموعات سريرية مثل (Group A, Group B)
        $groupLetter = $this->faker->unique()->lexify('Group ?');

        return [
            'group_name' => strtoupper($groupLetter),
            // عادةً التدريب السريري يبدأ من السنة الرابعة والخامسة
            'academic_year' => $this->faker->numberBetween(4, 5),
        ];
    }
}
