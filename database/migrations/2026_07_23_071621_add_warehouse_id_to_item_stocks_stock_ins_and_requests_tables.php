<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan gudang pada stok, stok masuk, dan pengajuan.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Tambahkan warehouse_id ke item_stocks
        |--------------------------------------------------------------------------
        | Pemeriksaan hasColumn diperlukan karena migration sebelumnya sudah
        | sempat berjalan sebagian sebelum gagal.
        */
        if (! Schema::hasColumn('item_stocks', 'warehouse_id')) {
            Schema::table('item_stocks', function (Blueprint $table) {
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->after('entity_id')
                    ->constrained('warehouses')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Tambahkan warehouse_id ke stock_ins
        |--------------------------------------------------------------------------
        */
        if (! Schema::hasColumn('stock_ins', 'warehouse_id')) {
            Schema::table('stock_ins', function (Blueprint $table) {
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->after('entity_id')
                    ->constrained('warehouses')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Tambahkan warehouse_id ke requests
        |--------------------------------------------------------------------------
        */
        if (! Schema::hasColumn('requests', 'warehouse_id')) {
            Schema::table('requests', function (Blueprint $table) {
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->after('entity_id')
                    ->constrained('warehouses')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Tentukan gudang default untuk data lama
        |--------------------------------------------------------------------------
        | Semua data lama sementara ditempatkan di Gudang Pabrik.
        */
        $defaultWarehouseId = DB::table('warehouses')
            ->where('code', 'GD-PABRIK')
            ->value('id');

        if (! $defaultWarehouseId) {
            throw new RuntimeException(
                'Gudang default GD-PABRIK tidak ditemukan. Jalankan WarehouseSeeder terlebih dahulu.'
            );
        }

        DB::table('item_stocks')
            ->whereNull('warehouse_id')
            ->update([
                'warehouse_id' => $defaultWarehouseId,
            ]);

        DB::table('stock_ins')
            ->whereNull('warehouse_id')
            ->update([
                'warehouse_id' => $defaultWarehouseId,
            ]);

        DB::table('requests')
            ->whereNull('warehouse_id')
            ->update([
                'warehouse_id' => $defaultWarehouseId,
            ]);

        /*
        |--------------------------------------------------------------------------
        | 5. Buat index biasa untuk entity_id
        |--------------------------------------------------------------------------
        | MySQL memerlukan index untuk foreign key entity_id.
        |
        | Index unik lama entity_id + item_id sebelumnya juga berfungsi sebagai
        | index foreign key. Karena index unik tersebut akan dihapus, kita harus
        | membuat index biasa entity_id terlebih dahulu.
        */
        if (! $this->indexExists(
            'item_stocks',
            'item_stocks_entity_id_index'
        )) {
            Schema::table('item_stocks', function (Blueprint $table) {
                $table->index(
                    'entity_id',
                    'item_stocks_entity_id_index'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Hapus unique lama entity_id + item_id
        |--------------------------------------------------------------------------
        */
        if ($this->indexExists(
            'item_stocks',
            'item_stocks_entity_id_item_id_unique'
        )) {
            Schema::table('item_stocks', function (Blueprint $table) {
                $table->dropUnique(
                    'item_stocks_entity_id_item_id_unique'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Buat unique baru
        |--------------------------------------------------------------------------
        | Satu barang dapat memiliki stok berbeda pada gudang yang berbeda.
        |
        | Kombinasi:
        | entity_id + warehouse_id + item_id
        */
        if (! $this->indexExists(
            'item_stocks',
            'item_stocks_entity_warehouse_item_unique'
        )) {
            Schema::table('item_stocks', function (Blueprint $table) {
                $table->unique(
                    [
                        'entity_id',
                        'warehouse_id',
                        'item_id',
                    ],
                    'item_stocks_entity_warehouse_item_unique'
                );
            });
        }
    }

    /**
     * Mengembalikan struktur database ke kondisi sebelumnya.
     */
    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Hapus unique stok per gudang
        |--------------------------------------------------------------------------
        */
        if ($this->indexExists(
            'item_stocks',
            'item_stocks_entity_warehouse_item_unique'
        )) {
            Schema::table('item_stocks', function (Blueprint $table) {
                $table->dropUnique(
                    'item_stocks_entity_warehouse_item_unique'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Kembalikan unique lama
        |--------------------------------------------------------------------------
        | Rollback hanya dapat dilakukan apabila belum terdapat dua stok barang
        | yang sama dalam entitas yang sama pada gudang berbeda.
        */
        if (! $this->indexExists(
            'item_stocks',
            'item_stocks_entity_id_item_id_unique'
        )) {
            Schema::table('item_stocks', function (Blueprint $table) {
                $table->unique(
                    [
                        'entity_id',
                        'item_id',
                    ],
                    'item_stocks_entity_id_item_id_unique'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Hapus index entity_id tambahan
        |--------------------------------------------------------------------------
        | Jangan dihapus sebelum unique lama berhasil dibuat kembali.
        */
        if ($this->indexExists(
            'item_stocks',
            'item_stocks_entity_id_index'
        )) {
            Schema::table('item_stocks', function (Blueprint $table) {
                $table->dropIndex(
                    'item_stocks_entity_id_index'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Hapus warehouse_id
        |--------------------------------------------------------------------------
        */
        if (Schema::hasColumn('requests', 'warehouse_id')) {
            Schema::table('requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('warehouse_id');
            });
        }

        if (Schema::hasColumn('stock_ins', 'warehouse_id')) {
            Schema::table('stock_ins', function (Blueprint $table) {
                $table->dropConstrainedForeignId('warehouse_id');
            });
        }

        if (Schema::hasColumn('item_stocks', 'warehouse_id')) {
            Schema::table('item_stocks', function (Blueprint $table) {
                $table->dropConstrainedForeignId('warehouse_id');
            });
        }
    }

    /**
     * Memeriksa apakah suatu index sudah tersedia.
     */
    private function indexExists(
        string $tableName,
        string $indexName
    ): bool {
        $databaseName = DB::connection()
            ->getDatabaseName();

        $index = DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', $tableName)
            ->where('index_name', $indexName)
            ->first();

        return $index !== null;
    }
};