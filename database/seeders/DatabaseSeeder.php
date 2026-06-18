<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed database aplikasi ATK/RTK.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Seeder User Awal
        |--------------------------------------------------------------------------
        | User default untuk testing login berdasarkan role.
        */

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
                'role' => 'staff',
            ]
        );

        User::updateOrCreate(
            ['email' => 'approver@example.com'],
            [
                'name' => 'Approver User',
                'password' => Hash::make('password'),
                'role' => 'approver',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Seeder Master Data
        |--------------------------------------------------------------------------
        | Urutan penting:
        | 1. EntitySeeder dulu untuk membuat ANR dan MAA.
        | 2. CategorySeeder untuk kategori barang.
        | 3. ItemSeeder untuk data barang awal.
        | 4. ItemStockSeeder untuk membuat stok per entitas ANR dan MAA.
        */

        $this->call([
            EntitySeeder::class,
            CategorySeeder::class,
            ItemSeeder::class,
            ItemStockSeeder::class,
        ]);
    }
}