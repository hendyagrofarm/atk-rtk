<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengeluaran barang langsung oleh admin pemegang stok (tanpa pengajuan).
     *
     * - stock_outs      : satu transaksi pengeluaran (siapa penerima, dari gudang mana).
     * - stock_out_items : barang dan jumlah per transaksi, beserta stok sebelum keluar.
     */
    public function up(): void
    {
        Schema::create('stock_outs', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->unique();
            $table->date('issue_date');
            $table->foreignId('entity_id')->constrained('entities')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('recipient_name', 150);
            $table->string('recipient_department', 150)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('issue_date');
            $table->index(['entity_id', 'warehouse_id']);
        });

        Schema::create('stock_out_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_out_id')->constrained('stock_outs')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->integer('quantity');
            $table->integer('stock_before')->nullable();
            $table->timestamps();

            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_out_items');
        Schema::dropIfExists('stock_outs');
    }
};
