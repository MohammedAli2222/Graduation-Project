<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Group;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Receptionist
        $receptionist = User::forceCreate([
            'first_name' => 'سوزان',
            'last_name' => 'الخطيب',
            'email' => 'reception@dentsyria.edu.sy',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $receptionist->assignRole('receptionist');

        // 2. Department Heads
        $deptHeads = [
            ['first' => 'خالد', 'last' => 'الزين', 'dept' => 'قسم المداواة والترميم'],
            ['first' => 'إبراهيم', 'last' => 'الحسين', 'dept' => 'قسم جراحة الفم والفكين'],
            ['first' => 'طارق', 'last' => 'المصري', 'dept' => 'قسم التعويضات السنية'],
            ['first' => 'مريم', 'last' => 'القاضي', 'dept' => 'قسم طب أسنان الأطفال والوقائي'],
            ['first' => 'وائل', 'last' => 'الرفاعي', 'dept' => 'قسم أمراض النسج حول السنية'],
        ];

        foreach ($deptHeads as $index => $dh) {
            $user = User::forceCreate([
                'first_name' => $dh['first'],
                'last_name' => $dh['last'],
                'email' => "head{$index}@dentsyria.edu.sy",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('department_head');
            
            $dept = Department::where('name', $dh['dept'])->first();
            if ($dept) {
                $user->departmentHeadProfile()->create([
                    'department_id' => $dept->id,
                ]);
            }
        }

        // 3. Instructors
        $instructorsData = [
            ['first' => 'أحمد', 'last' => 'العمر', 'specialty' => 'اختصاصي مداواة ترميمية'],
            ['first' => 'عمر', 'last' => 'السيد', 'specialty' => 'اختصاصي جراحة فم وفكين'],
            ['first' => 'يامن', 'last' => 'الشيخ', 'specialty' => 'اختصاصي تعويضات سنية ثابتة'],
            ['first' => 'فراس', 'last' => 'البني', 'specialty' => 'اختصاصي طب أسنان الأطفال'],
            ['first' => 'مصطفى', 'last' => 'النابلسي', 'specialty' => 'اختصاصي تقويم أسنان وفكين'],
            ['first' => 'فاطمة', 'last' => 'الصالح', 'specialty' => 'اختصاصي أمراض النسج حول السنية'],
            ['first' => 'شام', 'last' => 'الأسعد', 'specialty' => 'اختصاصي طب الفم وأمراضه'],
            ['first' => 'علي', 'last' => 'الكردي', 'specialty' => 'اختصاصي معالجة جذور الأسنان (لبية)'],
        ];

        foreach ($instructorsData as $index => $inst) {
            $user = User::forceCreate([
                'first_name' => $inst['first'],
                'last_name' => $inst['last'],
                'email' => "dr.instructor{$index}@dentsyria.edu.sy",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('instructor');
            $profile = $user->instructorProfile()->create([
                'phone' => '09' . rand(3,9) . rand(1000000, 9999999),
                'specialty' => $inst['specialty'],
                'specialty_year' => (string)rand(2015, 2022),
            ]);

            $randomGroups = Group::inRandomOrder()->limit(3)->pluck('id');
            $profile->groups()->sync($randomGroups);
        }

        // 4. Students
        $studentNames = [
            ['محمد', 'الأحمد'], ['أحمد', 'الخطيب'], ['خالد', 'السيد'], ['عمر', 'الزين'],
            ['يامن', 'العمر'], ['فراس', 'المصري'], ['وائل', 'الأسعد'], ['مصطفى', 'النابلسي'],
            ['مريم', 'الشيخ'], ['فاطمة', 'البني'], ['شام', 'الحسين'], ['ريم', 'الصالح'],
            ['لانا', 'الكردي'], ['تالا', 'القاضي'], ['نرمين', 'السلمان'], ['جوري', 'الرفاعي'],
            ['أمل', 'الجابر'], ['سارة', 'العثمان'], ['هلا', 'الدرويش'], ['رفيف', 'الحمصي'],
        ];

        $academicOptions = [
            ['academic_year' => 4, 'semester' => 1],
            ['academic_year' => 4, 'semester' => 2],
            ['academic_year' => 5, 'semester' => 1],
            ['academic_year' => 5, 'semester' => 2],
        ];

        foreach ($studentNames as $index => $name) {
            $user = User::forceCreate([
                'first_name' => $name[0],
                'last_name' => $name[1],
                'email' => "student{$index}@dentsyria.edu.sy",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('student');

            $selected = collect($academicOptions)->random();
            $group = Group::where('academic_year', $selected['academic_year'])->inRandomOrder()->first();

            $user->studentProfile()->create([
                'group_id' => $group->id ?? 1,
                'phone' => '09' . rand(3,9) . rand(1000000, 9999999),
                'university' => 'جامعة دمشق - كلية طب الأسنان',
                'exam_number' => '2024' . str_pad((string)($index + 1), 4, '0', STR_PAD_LEFT),
                'academic_year' => $selected['academic_year'],
                'semester' => $selected['semester'],
            ]);
        }

        // 5. Store Owner
        $storeOwner = User::forceCreate([
            'first_name' => 'سامر',
            'last_name' => 'البني',
            'email' => 'store@dentsyria.edu.sy',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $storeOwner->assignRole('store_owner');
        $storeOwner->storeProfile()->create([
            'store_name' => 'مستودع البني لطب الأسنان',
            'store_phone' => '0114444444',
            'store_address' => 'دمشق - البحصة'
        ]);
    }
}
