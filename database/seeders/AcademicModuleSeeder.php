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
    /**
     * تشغيل بذور قاعدة البيانات للقسم الأكاديمي.
     */
    public function run(): void
    {
        $this->command->info('جاري بناء الهيكل الأكاديمي (أقسام، مقررات، مجموعات، مشرفين)...');

        // 1. توليد الأقسام الأكاديمية (7 أقسام أساسية)
        $departments = Department::factory(7)->create();

        // 2. توليد مقررات دراسية وربطها بالأقسام العشوائية
        foreach ($departments as $department) {
            Course::factory(3)->create([
                'department_id' => $department->id,
                // توليد أسماء مقررات منطقية للقسم
                'name' => 'مقرر ' . explode(' ', $department->name)[0] . ' سريري',
            ]);
        }

        // 3. توليد 10 مجموعات للطلاب (سنة رابعة وخامسة)
        $groups = Group::factory(10)->create();

        // 4. إنشاء المشرفين (Instructors) وربطهم بالمجموعات
        InstructorProfile::factory(15)->make()->each(function (InstructorProfile $profile) use ($groups) {

            // إنشاء مستخدم جديد للمشرف وتعيين الصلاحية (بافتراض استخدام Spatie Permission)
            $user = User::factory()->create();
            $user->assignRole('instructor');

            // ربط ملف المشرف بالمستخدم وحفظه
            $profile->user_id = $user->id;
            $profile->save();

            // 5. ربط المشرف بـ 1 إلى 3 مجموعات سريرية عشوائياً (الجدول الوسيط)
            // استخدام واجهة DB مباشرة لضمان العمل حتى لو لم يتم تعريف العلاقات في المودل بعد
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
