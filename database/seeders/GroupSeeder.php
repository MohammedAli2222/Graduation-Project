<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['group_name' => 'الفئة الأولى'],
            ['group_name' => 'الفئة الثانية'],
            ['group_name' => 'الفئة الثالثة'],
            ['group_name' => 'الفئة الرابعة'],
            ['group_name' => 'الفئة الخامسة'],
            ['group_name' => 'الفئة السادسة'],
        ];

        foreach ($groups as $group) {
            Group::updateOrCreate(['group_name' => $group['group_name']], $group);
        }
    }
}
