<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StoreProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * تخزين كلمة المرور المشفرة مؤقتاً لتجنب إعادة تشفيرها 500+ مرة أثناء الـ Seeding.
     */
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * تحويل هذا المستخدم إلى صاحب متجر أدوات سنية رسمي،
     * مع تعيين الصلاحيات (Role) وإنشاء ملف المتجر الشخصي.
     */
    public function asStoreOwner(): static
    {
        return $this->afterCreating(function (User $user): void {
            if (! $user->hasRole('store_owner')) {
                $user->assignRole('store_owner');
            }

            if (! $user->storeProfile) {
                StoreProfile::factory()->for($user)->create();
            }
        });
    }

    /**
     * تحويل هذا المستخدم إلى طالب طب أسنان في الجامعة،
     * مع تعيين الصلاحيات وإنشاء الملف الشخصي للطالب.
     */
    public function student(): static
    {
        return $this->afterCreating(function (User $user): void {
            if (! $user->hasRole('student')) {
                $user->assignRole('student');
            }

            if (! $user->studentProfile) {
                StudentProfile::factory()->for($user)->create();
            }
        });
    }
}
