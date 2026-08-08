<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StoreProfile;
use App\Models\User;
use Database\Factories\Concerns\GeneratesArabicContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreProfile>
 */
class StoreProfileFactory extends Factory
{
    use GeneratesArabicContent;

    protected $model = StoreProfile::class;

    private const SUFFIXES = [
        'لوازم طب الأسنان', 'مستودع الأسنان', 'شركة تجارة المستلزمات السنية', 'دار معدات الأسنان',
        'مستودع اللوازم الطبية السنية', 'ماركت طب الأسنان', 'موزعو العناية الفموية', 'شركة الأدوات السنية',
        'الاستيراد والتصدير لمستلزمات الأسنان', 'مجموعة الحلول السنية',
    ];

    private const CITIES = [
        'دمشق', 'حلب', 'حمص', 'اللاذقية', 'طرطوس', 'حماة', 'درعا', 'دير الزور',
    ];

    public function definition(): array
    {
        // نستخدم مولّد Faker العربي (ar_SA) لاسم العائلة والحي حتى تكون بيانات المتاجر عربية واقعية
        $baseName = $this->arFaker()->unique()->lastName();
        $district = $this->arFaker()->streetName();

        return [
            'user_id' => User::factory(),
            'store_name' => "{$baseName} " . $this->faker->randomElement(self::SUFFIXES),
            'store_phone' => $this->faker->numerify('09########'),
            'store_address' => $district . ' - ' . $this->faker->randomElement(self::CITIES),
        ];
    }
}
