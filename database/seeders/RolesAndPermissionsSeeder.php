<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $viewWaitingList = Permission::create(['name' => 'view waiting list']);


        $receptionistRole = Role::findByName('receptionist');
       // $instructorRole = Role::findByName('instructor');

        $receptionistRole->givePermissionTo($viewWaitingList);
        //$instructorRole->givePermissionTo($viewWaitingList);
    }
}
