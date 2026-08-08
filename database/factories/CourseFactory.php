<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    // أسماء مقررات سريرية واقعية تغطي المستويين الرابع والخامس، تُستخدم كقيمة افتراضية
    // عندما يُستدعى Course::factory() مباشرة دون تمرير اسم محدد من الـ Seeder
    private const COURSE_NAMES = [
        'مداواة الأسنان اللبية (1)', 'مداواة الأسنان اللبية (2)', 'مداواة الأسنان اللبية (3)',
        'مداواة الأسنان المحافظة (3)', 'مداواة الأسنان المحافظة (4)',
        'التخدير والقلع (1)', 'التخدير والقلع (2)', 'التخدير والقلع (3)',
        'الجراحة الفموية وزرع الأسنان', 'تعويضات الأسنان الثابتة (3)', 'تعويضات الأسنان الثابتة (4)',
        'تعويضات الأسنان المتحركة (2)', 'تعويضات الأسنان المتحركة (3)',
        'تقويم الأسنان والفكين (1)', 'تقويم الأسنان والفكين (2)',
        'أمراض النسج حول السنية (2)', 'أمراض النسج حول السنية (3)',
        'طب أسنان الأطفال (1)', 'طب أسنان الأطفال (2)', 'أمراض الفم (1)',
    ];

    public function definition(): array
    {
        $year = $this->faker->numberBetween(4, 5);
        $semester = $this->faker->numberBetween(1, 2);

        return [
            'name' => $this->faker->unique()->randomElement(self::COURSE_NAMES),
            'department_id' => Department::factory(),
            'year' => $year,
            'semester' => $semester,
        ];
    }
}
