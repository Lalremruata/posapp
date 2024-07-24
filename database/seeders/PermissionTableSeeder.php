<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'permission_create',
            ],
            [
                'name' => 'permission_edit',
            ],
            [
                'name' => 'permission_delete',
            ],
            [
                'name' => 'permission_show',
            ],
            [
                'name' => 'permission_access',
            ],
            [
                'name' => 'role_create',
            ],
            [
                'name' => 'role_edit',
            ],
            [
                'name' => 'role_show',
            ],
            [
                'name' => 'role_delete',
            ],
            [
                'name' => 'role_access',
            ],
            [
                'name' => 'user_create',
            ],
            [
                'name' => 'user_edit',
            ],
            [
                'name' => 'user_show',
            ],
            [
                'name' => 'user_delete',
            ],
            [
                'name' => 'user_access',
            ],
            [
                'name' => 'stock_access',
            ],
            [
                'name' => 'stock_create',
            ],
            [
                'name' => 'stock_edit',
            ],
            [
                'name' => 'stock_show',
            ],
            [
                'name' => 'stock_delete',
            ],
            [
                'name' => 'stock_access',
            ],
            [
                'name' => 'supplier_create',
            ],
            [
                'name' => 'supplier_edit',
            ],
            [
                'name' => 'supplier_show',
            ],
            [
                'name' => 'supplier_delete',
            ],
            [
                'name' => 'supplier_access',
            ],
            
        ];
        Permission::insert($permissions);
    }
}
