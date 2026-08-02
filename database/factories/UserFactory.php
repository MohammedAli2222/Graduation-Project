<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
}
