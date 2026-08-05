<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Cache::flush();

        // 1. الأساسيات والبيانات المرجعية (Base Data & Lookups)
        $this->call([
            RoleSeeder::class,
            GroupSeeder::class,
            RolesAndPermissionsSeeder::class,
            DepartmentAndCourseSeeder::class,
            CaseTypeSeeder::class,
            CategorySeeder::class,
            UserSeeder::class,
        ]);

        // 2. إنشاء المستخدمين والهيكل الأكاديمي المتقدم
        $this->call([
            StoreSeeder::class,
            StudentSeeder::class,
        ]);

        // 3. القسم الطبي الشامل (Medical Module)
        // $this->call([
        //     MedicalModuleSeeder::class, // ينشئ المرضى، التشخيصات، العلاجات، والمواعيد
        // ]);

        // 4. التجارة الإلكترونية الأساسية والمتقدمة (E-commerce Module)
        $this->call([
            ProductSeeder::class,
            OrderSeeder::class,
            EcommerceModuleSeeder::class, // ينشئ العروض الترويجية والسلات للطلاب
        ]);

        // 5. إنشاء حساب موظف الاستقبال (Receptionist)
        $receptionist = User::where('email', 'receptionist@hospital.com')->first();
        if (! $receptionist) {
            $receptionist = User::forceCreate([
                'email' => 'receptionist@hospital.com',
                'first_name' => 'Receptionist',
                'last_name' => 'Hospital',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]);
        }

        if (! $receptionist->hasRole('receptionist')) {
            $receptionist->assignRole('receptionist');
        }
    }
}
