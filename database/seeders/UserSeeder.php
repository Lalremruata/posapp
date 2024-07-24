<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'remember_token' => null,
            'store_id' => '1',
            ],
            [
                'name' => 'Agent',
                'email' => 'agent@agent.com',
                'password' => bcrypt('password'),
                'remember_token' => null,
                'store_id' => '2',
            ],
        ];
        User::insert($users);
    }
}
