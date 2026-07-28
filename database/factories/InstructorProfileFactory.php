<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InstructorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstructorProfile>
 */
class InstructorProfileFactory extends Factory
{
    protected $model = InstructorProfile::class;

    private const SPECIALTIES = [
        'أخصائي مداواة لبية',
        'جراح فم وفكين',
        'أخصائي تعويضات سنية',
        'أخصائي أمراض لثة',
        'أخصائي طب أسنان أطفال',
        'أستاذ دكتور (بروفيسور)',
        'طبيب مقيم - دراسات عليا'
    ];

    public function definition(): array
    {
        return [
            // سيتم تمرير المستخدم وتحديد دوره في الـ Seeder
            'user_id' => User::factory(),
            'phone' => $this->faker->numerify('09########'),
            'specialty' => $this->faker->randomElement(self::SPECIALTIES),
            'specialty_year' => $this->faker->randomElement(['سنة أولى', 'سنة ثانية', 'بورد سوري', 'دكتوراه']),
        ];
    }
}
