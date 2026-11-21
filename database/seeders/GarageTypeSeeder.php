<?php

namespace Database\Seeders;

use App\Models\GarageType;
use Illuminate\Database\Seeder;

class GarageTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Sepeda', 'prefix' => 'S'],
            ['name' => 'Mobil', 'prefix' => 'C'],
            ['name' => 'Mobil Listrik', 'prefix' => 'E'],
        ];

        foreach ($types as $type) {
            GarageType::firstOrCreate(
                ['prefix' => $type['prefix']],
                ['name' => $type['name']],
            );
        }
    }
}