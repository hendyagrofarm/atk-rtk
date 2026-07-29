<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan dukungan pengajuan barang custom.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('request_type', ['stock', 'custom'])
                ->default('stock')
                ->after('request_date');

            $table->decimal('estimated_total', 15, 2)
                ->nullable()
                ->after('note');
        });

        Schema::table('request_details', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Ubah item_id menjadi nullable
            |--------------------------------------------------------------------------
            | Pengajuan stok tetap menggunakan item_id.
            | Pengajuan custom tidak menggunakan item_id.
            */
            $table->dropForeign(['item_id']);
        });

        Schema::table('request_details', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')
                ->nullable()
                ->change();

            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('custom_item_name')
                ->nullable()
                ->after('item_id');

            $table->string('custom_category')
                ->nullable()
                ->after('custom_item_name');

            $table->text('specification')
                ->nullable()
                ->after('custom_category');

            $table->string('unit')
                ->nullable()
                ->after('approved_quantity');

            $table->decimal('estimated_price', 15, 2)
                ->nullable()
                ->after('unit');

            $table->string('product_url', 1000)
                ->nullable()
                ->after('estimated_price');
        });
    }

    /**
     * Menghapus dukungan pengajuan custom.
     */
    public function down(): void
    {
        Schema::table('request_details', function (Blueprint $table) {
            $table->dropForeign(['item_id']);

            $table->dropColumn([
                'custom_item_name',
                'custom_category',
                'specification',
                'unit',
                'estimated_price',
                'product_url',
            ]);
        });

        Schema::table('request_details', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')
                ->nullable(false)
                ->change();

            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn([
                'request_type',
                'estimated_total',
            ]);
        });
    }
};