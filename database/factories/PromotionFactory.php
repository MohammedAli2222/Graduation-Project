<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    private const PROMO_TITLES = [
        'عروض بداية الفصل الدراسي (Back to School)',
        'خصم خاص على أدوات الجراحة (Surgery Sale)',
        'تخفيضات الكومبوزيت والمواد الترميمية (Restorative Promo)',
        'عرض التصفية نهاية العام (Year-End Clearance)',
        'خصم مميز لطلاب السنة الرابعة (4th Year Special)',
        'باقة العيادة المتكاملة (Full Clinic Bundle)',
    ];

    public function definition(): array
    {
        $isActive = $this->faker->boolean(80); // 80% من العروض نشطة
        $startDate = $this->faker->dateTimeBetween('-1 month', '+1 week');
        $endDate = Carbon::instance($startDate)->addDays($this->faker->numberBetween(5, 30));

        return [
            // سيتم استبدال الـ store_id لاحقاً في الـ Seeder
            'store_id' => User::factory()->asStoreOwner(),
            'title' => $this->faker->randomElement(self::PROMO_TITLES),
            'description' => $this->faker->realText(50),
            'discount_percentage' => $this->faker->randomFloat(2, 5, 50), // خصم من 5% إلى 50%
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => $isActive,
        ];
    }
}
