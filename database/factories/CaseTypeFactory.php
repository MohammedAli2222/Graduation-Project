<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CaseType;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseType>
 */
class CaseTypeFactory extends Factory
{
    protected $model = CaseType::class;

    private const PROCEDURE_NAMES = [
        'حشوة كومبوزيت (سطح واحد)',
        'حشوة كومبوزيت (سطحين أو أكثر)',
        'حشوة عنقية Class V',
        'حشوة أملغم خلفية',
        'علاج عصب أمامي وحيد الجذر',
        'علاج عصب ضاحك أحادي القناة',
        'علاج عصب ضاحك ثنائي القنوات',
        'علاج عصب ضرس خلفي متعدد الأقنية',
        'إعادة معالجة لبية فاشلة',
        'جراحة قمة الجذر (Apicoectomy)',
    ];

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'name' => $this->faker->randomElement(self::PROCEDURE_NAMES),
            'slots_needed' => $this->faker->numberBetween(1, 2),
            'required_count' => $this->faker->numberBetween(2, 6),
        ];
    }
}
