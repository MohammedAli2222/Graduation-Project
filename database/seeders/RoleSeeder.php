<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'receptionist']);
        Role::create(['name' => 'instructor']);
        Role::create(['name' => 'student']);
        Role::create(['name' => 'department_head']);
        Role::create(['name' => 'store_owner']);
    }
}
