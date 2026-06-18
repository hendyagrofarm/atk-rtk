<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemStock;
use Illuminate\Database\Seeder;

class ItemStockSeeder extends Seeder
{
    /**
     * Membuat data stok per entitas untuk semua barang yang sudah ada.
     */
    public function run(): void
    {
        $entities = Entity::where('is_active', true)->get();
        $items = Item::where('is_active', true)->get();

        foreach ($items as $item) {
            foreach ($entities as $entity) {
                ItemStock::updateOrCreate(
                    [
                        'entity_id' => $entity->id,
                        'item_id' => $item->id,
                    ],
                    [
                        'current_stock' => 0,
                        'minimum_stock' => $item->minimum_stock ?? 0,
                    ]
                );
            }
        }
    }
}