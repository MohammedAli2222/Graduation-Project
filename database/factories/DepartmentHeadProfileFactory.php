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

            'department_id' => Department::query()
                ->whereNotIn('id', DepartmentHeadProfile::query()->pluck('department_id'))
                ->inRandomOrder()
                ->value('id')
                ?? throw new RuntimeException('كل الأقسام لديها رئيس قسم بالفعل، يرجى عمل Seed لأقسام إضافية أولاً.'),
        ];
    }
}
