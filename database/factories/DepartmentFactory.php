<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * الأقسام الأكاديمية والسريرية الفعلية في كليات طب الأسنان.
     */
    private const CLINICAL_DEPARTMENTS = [
        'المداواة اللبية (Endodontics)',
        'جراحة الفم والفكين (Oral Surgery)',
        'التعويضات الثابتة والمتحركة (Prosthodontics)',
        'أمراض اللثة (Periodontics)',
        'طب أسنان الأطفال (Pediatric Dentistry)',
        'تقويم الأسنان (Orthodontics)',
        'المداواة الترميمية (Operative Dentistry)'
    ];

    public function definition(): array
    {
        return [
            // استخدام unique لضمان عدم تكرار اسم القسم
            'name' => $this->faker->unique()->randomElement(self::CLINICAL_DEPARTMENTS),
            // تخصيص عدد كراسي منطقي لكل قسم عيادي
            'total_chairs' => $this->faker->numberBetween(10, 30),
            'description' => $this->faker->realText(100),
        ];
    }
}
