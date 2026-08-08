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

        $this->call([
            RoleSeeder::class,
            GroupSeeder::class,
            RolesAndPermissionsSeeder::class,
            DepartmentAndCourseSeeder::class,
            CaseTypeSeeder::class,
            CategorySeeder::class,
            UserSeeder::class,
        ]);

        $this->call([
            StoreSeeder::class,
            StudentSeeder::class,
        ]);

        // $this->call([
        // ]);

        $this->call([
            ProductSeeder::class,
            OrderSeeder::class,
            EcommerceModuleSeeder::class,
        ]);

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
