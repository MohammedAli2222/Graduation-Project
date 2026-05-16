<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentAndCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. إنشاء قسم المداواة
        $restorative = Department::create([
            'name' => 'قسم المداواة والترميم',
            'description' => 'يشمل الحشوات التجميلية وسحب العصب'
        ]);

        // 2. إضافة مواد لهذا القسم
        $restorative->courses()->createMany([
            [
                'name' => 'مداواة ترميمية 1',
                'year' => '4',
                'semester' => '1'
            ],
            [
                'name' => 'مداواة لبيّة (سحب عصب)',
                'year' => '5',
                'semester' => '1'
            ]
        ]);

        // 3. إنشاء قسم الجراحة
        $surgery = Department::create([
            'name' => 'قسم الجراحة',
            'description' => 'يشمل القلع والعمليات الجراحية الفموية'
        ]);

        $surgery->courses()->create([
            'name' => 'قلع 1',
            'year' => '4',
            'semester' => '2'
        ]);
    }
}
