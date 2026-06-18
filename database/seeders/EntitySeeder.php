<?php

namespace Database\Seeders;

use App\Models\Entity;
use Illuminate\Database\Seeder;

class EntitySeeder extends Seeder
{
    /**
     * Jalankan seeder entitas perusahaan.
     */
    public function run(): void
    {
        Entity::updateOrCreate(
            ['code' => 'ANR'],
            [
                'name' => 'PT Agrofarm Nusa Raya',
                'is_active' => true,
            ]
        );

        Entity::updateOrCreate(
            ['code' => 'MAA'],
            [
                'name' => 'PT Mycotech Agro Asia',
                'is_active' => true,
            ]
        );
    }
}