<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use App\Models\Group;
use App\Models\InstructorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('جاري بناء الهيكل الأكاديمي (أقسام، مقررات، مجموعات، مشرفين)...');

        $departments = Department::factory(7)->create();

        foreach ($departments as $department) {
            Course::factory(3)->create([
                'department_id' => $department->id,
                'name' => 'مقرر ' . explode(' ', $department->name)[0] . ' سريري',
            ]);
        }

        $groups = Group::factory(10)->create();

        InstructorProfile::factory(15)->make()->each(function (InstructorProfile $profile) use ($groups) {

            $user = User::factory()->create();
            $user->assignRole('instructor');

            $profile->user_id = $user->id;
            $profile->save();

            $randomGroupIds = $groups->random(rand(1, 3))->pluck('id');

            foreach ($randomGroupIds as $groupId) {
                DB::table('group_instructor')->insert([
                    'instructor_profile_id' => $profile->id,
                    'group_id' => $groupId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->command->info('تم إنشاء الهيكل الأكاديمي وربط المشرفين بالمجموعات بنجاح!');
    }
}
