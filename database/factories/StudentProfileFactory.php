<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Group;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    protected $model = StudentProfile::class;

    private const UNIVERSITIES = [
        'University of Damascus - Faculty of Dentistry',
        'University of Aleppo - Faculty of Dentistry',
        'Tishreen University - Faculty of Dentistry',
        'Al-Baath University - Faculty of Dentistry',
        'Syrian Private University - Faculty of Dentistry',
        'Arab International University - Faculty of Dentistry',
        'International University for Science and Technology - Faculty of Dentistry',
        'Al-Wataniya Private University - Faculty of Dentistry',
    ];

    public function definition(): array
    {
        // Groups are seeded first (GroupSeeder), so this pulls a real, existing group
        // and mirrors its academic_year onto the profile to keep the two in sync.
        $group = Group::query()->inRandomOrder()->first()
            ?? throw new RuntimeException('Seed groups before generating student profiles.');

        return [
            'user_id' => User::factory(),
            'group_id' => $group->id,
            'phone' => $this->faker->numerify('09########'),
            'exam_number' => $this->faker->unique()->numerify('EX-######'),
            'university' => $this->faker->randomElement(self::UNIVERSITIES),
            'academic_year' => $group->academic_year,
            'semester' => $this->faker->numberBetween(1, 2),
        ];
    }
}
