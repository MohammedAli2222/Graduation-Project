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

    /**
     * Seed the application's database.
     *
     * Order matters: each group below depends on rows created by the
     * previous one, so this must stay sequential to avoid FK violations.
     *   1. Roles, groups, permissions, departments/courses, case types, categories.
     *   2. Marketplace participants (stores, students) — depend on roles + groups.
     *   3. Catalog (products) — depends on stores + categories.
     *   4. Transactions (orders + order items) — depend on students + products.
     */
    public function run(): void
    {
        Cache::flush();

        $this->call([
            RoleSeeder::class,
            GroupSeeder::class,
            RolesAndPermissionsSeeder::class,
            DepartmentAndCourseSeeder::class,
            CaseTypeSeeder::class,
            CategorySeeder::class,
        ]);

        $this->call([
            StoreSeeder::class,
            StudentSeeder::class,
        ]);

        $this->call([
            ProductSeeder::class,
            OrderSeeder::class,
        ]);

        $receptionist = User::firstOrCreate(
            ['email' => 'receptionist@hospital.com'],
            [
                'first_name' => 'Receptionist',
                'last_name' => 'Hospital',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        if (! $receptionist->hasRole('receptionist')) {
            $receptionist->assignRole('receptionist');
        }
    }
}
