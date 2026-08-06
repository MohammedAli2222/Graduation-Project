<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\DepartmentHeadProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $maleFirstNames = ['محمد', 'أحمد', 'خالد', 'عمر', 'يامن', 'فراس', 'وائل', 'مصطفى', 'إبراهيم', 'علي', 'حسن', 'يزن', 'عبد الله', 'نور الدين', 'طارق', 'سامر', 'باسل', 'رامي', 'عماد', 'زياد'];
        $femaleFirstNames = ['مريم', 'فاطمة', 'شام', 'ريم', 'لانا', 'تالا', 'نرمين', 'جوري', 'أمل', 'سارة', 'هلا', 'رفيف', 'ميرال', 'دانا', 'نجاح', 'سوار', 'إيمان', 'رهف', 'يارا', 'لجين'];
        $lastNames = ['الأحمد', 'الخطيب', 'السيد', 'الزين', 'العمر', 'المصري', 'الأسعد', 'النابلسي', 'الشيخ', 'البني', 'الحسين', 'الصالح', 'الكردي', 'القاضي', 'السلمان', 'الرفاعي', 'الجابر', 'العثمان', 'الدرويش', 'الحمصي'];

        $isMale = fake()->boolean();
        $firstName = $isMale ? fake()->randomElement($maleFirstNames) : fake()->randomElement($femaleFirstNames);
        $lastName = fake()->randomElement($lastNames);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function asStoreOwner(): static
    {
        return $this->afterCreating(function (\App\Models\User $user) {
            if (! $user->hasRole('store_owner')) {
                $user->assignRole('store_owner');
            }
            if (! $user->storeProfile) {
                $user->storeProfile()->create([
                    'store_name' => 'مستودع ' . $user->last_name . ' للوازم طب الأسنان',
                    'store_phone' => '011' . rand(2000000, 9999999),
                    'store_address' => 'دمشق - ' . fake()->randomElement(['البحصة', 'الحريقة', 'المزة', 'كفرسوسة']),
                ]);
            }
        });
    }

    public function student(): static
    {
        return $this->afterCreating(function (\App\Models\User $user) {
            if (! $user->hasRole('student')) {
                $user->assignRole('student');
            }
            if (! $user->studentProfile) {
                $academicYear = fake()->randomElement([4, 5]);
                $group = \App\Models\Group::where('academic_year', $academicYear)->inRandomOrder()->first();

                $user->studentProfile()->create([
                    'group_id' => $group?->id ?? 1,
                    'phone' => '09' . rand(3, 9) . rand(1000000, 9999999),
                    'university' => 'جامعة دمشق - كلية طب الأسنان',
                    'exam_number' => fake()->unique()->numerify('2024#####'),
                    'academic_year' => $academicYear,
                    'semester' => fake()->randomElement([1, 2]),
                ]);
            }
        });
    }

    public function instructor(): static
    {
        return $this->afterCreating(function (\App\Models\User $user) {
            if (! $user->hasRole('instructor')) {
                $user->assignRole('instructor');
            }
            if (! $user->instructorProfile) {
                $specialties = [
                    'اختصاصي مداواة ترميمية',
                    'اختصاصي معالجة جذور الأسنان (لبية)',
                    'اختصاصي جراحة فم وفكين',
                    'اختصاصي تعويضات سنية ثابتة',
                    'اختصاصي تعويضات سنية متحركة',
                    'اختصاصي طب أسنان الأطفال',
                    'اختصاصي تقويم أسنان وفكين',
                    'اختصاصي أمراض النسج حول السنية',
                ];

                $user->instructorProfile()->create([
                    'phone' => '09' . rand(3, 9) . rand(1000000, 9999999),
                    'specialty' => fake()->randomElement($specialties),
                    'specialty_year' => (string) fake()->numberBetween(2012, 2022),
                ]);
            }
        });
    }

    /**
     * ينشئ مستخدماً برتبة "رئيس قسم" (department_head) ويربطه بقسم أكاديمي حقيقي.
     * إن لم يُمرَّر قسم محدد، يُختار أول قسم غير مُسنَد له رئيس بعد (department_id
     * فريد في department_head_profiles: رئيس واحد فقط لكل قسم).
     */
    public function departmentHead(?Department $department = null): static
    {
        return $this->afterCreating(function (\App\Models\User $user) use ($department) {
            if (! $user->hasRole('department_head')) {
                $user->assignRole('department_head');
            }

            if (! $user->departmentHeadProfile) {
                $targetDepartment = $department ?? Department::query()
                    ->whereNotIn('id', DepartmentHeadProfile::query()->pluck('department_id'))
                    ->inRandomOrder()
                    ->first();

                if (! $targetDepartment) {
                    throw new RuntimeException('لا يوجد قسم متاح لإسناد رئيس قسم جديد له (كل الأقسام لديها رئيس بالفعل).');
                }

                $user->departmentHeadProfile()->create([
                    'department_id' => $targetDepartment->id,
                ]);
            }
        });
    }
}
