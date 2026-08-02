<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstructorProfileFactory extends Factory
{
    public function definition(): array
    {
        $specialties = [
            'اختصاصي مداواة ترميمية',
            'اختصاصي معالجة جذور الأسنان (لبية)',
            'اختصاصي جراحة فم وفكين',
            'اختصاصي تعويضات سنية ثابتة',
            'اختصاصي تعويضات سنية متحركة',
            'اختصاصي طب أسنان الأطفال',
            'اختصاصي تقويم أسنان وفكين',
            'اختصاصي أمراض النسج حول السنية',
            'اختصاصي طب الفم وأمراضه'
        ];

        return [
            'user_id' => User::factory(),
            'phone' => '09' . fake()->numerify('#######'),
            'specialty' => fake()->randomElement($specialties),
            'specialty_year' => (string)fake()->numberBetween(2012, 2022),
        ];
    }
}
