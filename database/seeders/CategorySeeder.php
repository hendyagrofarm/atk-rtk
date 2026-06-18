<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Jalankan seeder kategori.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'ATK',
                'description' => 'Alat tulis kantor',
            ],
            [
                'name' => 'RTK',
                'description' => 'Rumah tangga kantor',
            ],
            [
                'name' => 'Elektronik',
                'description' => 'Perlengkapan elektronik kantor',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                ]
            );
        }
    }
}