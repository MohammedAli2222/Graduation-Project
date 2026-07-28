<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PatientStatus;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    private const PRELIMINARY_DIAGNOSES = [
        'ألم حاد في الفك السفلي الأيمن مع تورم بسيط.',
        'تسوس عميق في السن 36 مع حساسية للبارد والساخن.',
        'نزيف في اللثة عند التفريش وتراكم للجير.',
        'فقدان السن 45 وحاجة لتعويض سني (زراعة أو جسر).',
        'تراجع لثوي في الأسنان الأمامية السفلية.',
        'ألم نابض مستمر، يزداد ليلاً (اشتباه التهاب عصب).',
        'كسر في التاج السني للسن الأمامي 11 إثر رض.',
        'بزوغ جزئي لضرس العقل مع التهاب حوائط التاج.',
    ];

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['male', 'female']);

        return [
            'patient_code' => $this->faker->unique()->numerify('PT-######'),
            'full_name' => $this->faker->name($gender === 'male' ? 'male' : 'female'),
            'birth_date' => $this->faker->dateTimeBetween('-60 years', '-12 years')->format('Y-m-d'),
            'gender' => $gender,
            'phone' => $this->faker->numerify('09########'),
            'address' => $this->faker->city() . ', ' . $this->faker->streetName(),
            'preliminary_diagnosis' => $this->faker->randomElement(self::PRELIMINARY_DIAGNOSES),
            'availability_status' => $this->faker->randomElement(array_column(PatientStatus::cases(), 'value')),
            // نفترض أن من أضاف المريض هو طالب
            'added_by' => User::factory()->student(),
        ];
    }
}
