<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // مصفوفة تحتوي على الفئات لكل من السنة الرابعة والسنة الخامسة
        $groups = [
            // فئات السنة الرابعة
            ['group_name' => 'الفئة الأولى', 'academic_year' => 4],
            ['group_name' => 'الفئة الثانية', 'academic_year' => 4],
            ['group_name' => 'الفئة الثالثة', 'academic_year' => 4],
            ['group_name' => 'الفئة الرابعة', 'academic_year' => 4],
            ['group_name' => 'الفئة الخامسة', 'academic_year' => 4],
            ['group_name' => 'الفئة السادسة', 'academic_year' => 4],

            // فئات السنة الخامسة
            ['group_name' => 'الفئة الأولى', 'academic_year' => 5],
            ['group_name' => 'الفئة الثانية', 'academic_year' => 5],
            ['group_name' => 'الفئة الثالثة', 'academic_year' => 5],
            ['group_name' => 'الفئة الرابعة', 'academic_year' => 5],
            ['group_name' => 'الفئة الخامسة', 'academic_year' => 5],
            ['group_name' => 'الفئة السادسة', 'academic_year' => 5],
        ];

        foreach ($groups as $group) {
            Group::updateOrCreate(
                [
                    'group_name' => $group['group_name'],
                    'academic_year' => $group['academic_year'],
                ],
                $group
            );
        }
    }
}
