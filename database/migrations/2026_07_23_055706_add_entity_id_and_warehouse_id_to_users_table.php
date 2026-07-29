<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan relasi entitas dan gudang pada user.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Entitas user
            |--------------------------------------------------------------------------
            | Dibuat nullable agar user lama tetap aman.
            |
            | Contoh:
            | Admin ANR
            | Staff MAA
            */
            $table->foreignId('entity_id')
                ->nullable()
                ->after('role')
                ->constrained('entities')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Gudang user
            |--------------------------------------------------------------------------
            | Dibuat nullable agar user lama tetap aman.
            |
            | Contoh:
            | Admin Gudang Pabrik
            | Staff Gudang Head Office
            */
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('entity_id')
                ->constrained('warehouses')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Index gabungan
            |--------------------------------------------------------------------------
            | Mempercepat filter user berdasarkan entitas dan gudang.
            */
            $table->index(
                ['entity_id', 'warehouse_id'],
                'users_entity_warehouse_index'
            );
        });
    }

    /**
     * Menghapus relasi entitas dan gudang dari user.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(
                'users_entity_warehouse_index'
            );

            $table->dropForeign([
                'warehouse_id',
            ]);

            $table->dropForeign([
                'entity_id',
            ]);

            $table->dropColumn([
                'entity_id',
                'warehouse_id',
            ]);
        });
    }
};