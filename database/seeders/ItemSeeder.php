<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Jalankan seeder barang.
     */
    public function run(): void
    {
        $atk = Category::where('name', 'ATK')->first();
        $rtk = Category::where('name', 'RTK')->first();
        $elektronik = Category::where('name', 'Elektronik')->first();

        if ($atk) {
            Item::updateOrCreate(
                ['code' => 'ATK-001'],
                [
                    'category_id' => $atk->id,
                    'name' => 'Pulpen Hitam',
                    'unit' => 'pcs',
                    'minimum_stock' => 10,
                    'current_stock' => 100,
                    'is_active' => true,
                ]
            );

            Item::updateOrCreate(
                ['code' => 'ATK-002'],
                [
                    'category_id' => $atk->id,
                    'name' => 'Kertas A4',
                    'unit' => 'rim',
                    'minimum_stock' => 5,
                    'current_stock' => 25,
                    'is_active' => true,
                ]
            );

            Item::updateOrCreate(
                ['code' => 'ATK-003'],
                [
                    'category_id' => $atk->id,
                    'name' => 'Map Plastik',
                    'unit' => 'pcs',
                    'minimum_stock' => 20,
                    'current_stock' => 80,
                    'is_active' => true,
                ]
            );
        }

        if ($rtk) {
            Item::updateOrCreate(
                ['code' => 'RTK-001'],
                [
                    'category_id' => $rtk->id,
                    'name' => 'Tisu',
                    'unit' => 'pack',
                    'minimum_stock' => 10,
                    'current_stock' => 40,
                    'is_active' => true,
                ]
            );

            Item::updateOrCreate(
                ['code' => 'RTK-002'],
                [
                    'category_id' => $rtk->id,
                    'name' => 'Sabun Cuci Tangan',
                    'unit' => 'botol',
                    'minimum_stock' => 5,
                    'current_stock' => 15,
                    'is_active' => true,
                ]
            );
        }

        if ($elektronik) {
            Item::updateOrCreate(
                ['code' => 'ELK-001'],
                [
                    'category_id' => $elektronik->id,
                    'name' => 'Mouse Wireless',
                    'unit' => 'pcs',
                    'minimum_stock' => 3,
                    'current_stock' => 10,
                    'is_active' => true,
                ]
            );
        }
    }
}