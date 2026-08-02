<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            // Year 4
            ['group_name' => 'المجموعة أ - السنة الرابعة', 'academic_year' => 4],
            ['group_name' => 'المجموعة ب - السنة الرابعة', 'academic_year' => 4],
            ['group_name' => 'المجموعة ج - السنة الرابعة', 'academic_year' => 4],
            ['group_name' => 'المجموعة د - السنة الرابعة', 'academic_year' => 4],
            
            // Year 5
            ['group_name' => 'المجموعة أ - السنة الخامسة', 'academic_year' => 5],
            ['group_name' => 'المجموعة ب - السنة الخامسة', 'academic_year' => 5],
            ['group_name' => 'المجموعة ج - السنة الخامسة', 'academic_year' => 5],
            ['group_name' => 'المجموعة د - السنة الخامسة', 'academic_year' => 5],
        ];

        foreach ($groups as $group) {
            Group::create($group);
        }
    }
}
