<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = [
            [
                'store_name'=>'Main Store',
                'store_type' => 'main',
            ],
            [
                'store_name'=>'Branch Store',
                'store_type' => 'branch',
            ],

        ];
        Store::insert($stores);
    }
}
