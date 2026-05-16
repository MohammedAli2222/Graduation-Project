<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            GroupSeeder::class,
            RolesAndPermissionsSeeder::class,
            DepartmentAndCourseSeeder::class,
            CaseTypeSeeder::class,          
        ]);

        $receptionist = User::create([
            'first_name' => 'Receptionist',
            'last_name' => 'Hospital',
            'email' => 'receptionist@hospital.com',
            'password' => Hash::make('password123'),
        ]);

        $receptionist->assignRole('receptionist');
    }
}
