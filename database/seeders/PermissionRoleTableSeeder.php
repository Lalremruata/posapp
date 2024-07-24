<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionRoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin_permissions = Permission::all();

        $employee_permissions = Permission::where('name', 'LIKE', 'permission_%')->get();

        Role::findOrFail(1)->permissions()->sync($admin_permissions);
        Role::findOrFail(2)->permissions()->sync($employee_permissions);
    }
}
