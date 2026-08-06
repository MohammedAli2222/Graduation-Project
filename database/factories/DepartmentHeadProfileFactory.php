<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use App\Models\DepartmentHeadProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends Factory<DepartmentHeadProfile>
 */
class DepartmentHeadProfileFactory extends Factory
{
    protected $model = DepartmentHeadProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            // department_id فريد (رئيس واحد لكل قسم)، لذا نختار قسماً لم يُسنَد له
            // رئيس بعد بدلاً من إنشاء قسم جديد في كل مرة أو الاصطدام بالقيد الفريد.
            // لا نستخدم علاقة Department::departmentHeadProfile() لأنها معرّفة بمفتاح
            // أجنبي خاطئ (user_id بدل department_id) في app/Models/Department.php.
            'department_id' => Department::query()
                ->whereNotIn('id', DepartmentHeadProfile::query()->pluck('department_id'))
                ->inRandomOrder()
                ->value('id')
                ?? throw new RuntimeException('كل الأقسام لديها رئيس قسم بالفعل، يرجى عمل Seed لأقسام إضافية أولاً.'),
        ];
    }
}
