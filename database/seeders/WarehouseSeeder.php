<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::updateOrCreate(
            [
                'code' => 'GD-PABRIK',
            ],
            [
                'name' => 'Gudang Pabrik',
                'location' => 'Area Pabrik',
                'description' => 'Gudang penyimpanan barang yang berada di area pabrik.',
                'is_active' => true,
            ]
        );

        Warehouse::updateOrCreate(
            [
                'code' => 'GD-HO',
            ],
            [
                'name' => 'Gudang Head Office',
                'location' => 'Head Office',
                'description' => 'Gudang penyimpanan barang yang berada di Head Office.',
                'is_active' => true,
            ]
        );
    }
}