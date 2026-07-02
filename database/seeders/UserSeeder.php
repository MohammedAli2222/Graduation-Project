<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Group;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $academicOptions = [
            ['academic_year' => 4, 'semester' => 1],
            ['academic_year' => 4, 'semester' => 2],
            ['academic_year' => 5, 'semester' => 1],
            ['academic_year' => 5, 'semester' => 2],
        ];

        // 1. توليد 15 طالب ببيانات عشوائية
        User::factory()->count(20)->create([
            'email_verified_at' => now(),
        ])->each(function ($user) use ($academicOptions) {

            // اختيار عشوائي لأحد الخيارات أعلاه
            $selected = collect($academicOptions)->random();

            $user->assignRole('student');
            $user->studentProfile()->create([
                'group_id' => Group::where('academic_year', $selected['academic_year'])
                    ->inRandomOrder()
                    ->first()->id,
                'phone'         => fake()->phoneNumber(),
                'university'    => 'Damascus University',
                'exam_number'   => '2026' . rand(1000, 9999),
                'academic_year' => $selected['academic_year'], // توزيع السنة
                'semester'      => $selected['semester'],      // توزيع الفصل
            ]);
        });

        // 2. إنشاء صاحب متجر واحد
        $storeOwner = User::create([
            'first_name' => 'أحمد',
            'last_name' => 'تاجر',
            'email' => 'shop@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $storeOwner->assignRole('store_owner');
        $storeOwner->storeProfile()->create([
            'store_name' => 'متجر التقنية',
            'store_phone' => '0988888888',
            'store_address' => 'Damascus'
        ]);

        // 3. إنشاء رئيس قسم واحد
        $head = User::create([
            'first_name' => 'دكتور',
            'last_name' => 'رئيس',
            'email' => 'head@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $head->assignRole('department_head');
        $head->departmentHeadProfile()->create([
            'department_id' => 1,
        ]);

        // 4. توليد 5 معيدين ببيانات عشوائية وربطهم بغروبات
        User::factory()->count(5)->create([
            'email_verified_at' => now(), // تفعيل البريد لكل معيد
        ])->each(function ($user) {
            $user->assignRole('instructor');
            $profile = $user->instructorProfile()->create([
                'phone' => fake()->phoneNumber(),
                'specialty' => 'Software Engineering',
                'specialty_year' => '2024',
            ]);

            $randomGroups = Group::inRandomOrder()->limit(3)->pluck('id');
            $profile->groups()->sync($randomGroups);
        });
    }
}
