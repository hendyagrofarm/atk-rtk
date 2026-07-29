<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->foreignId('entity_id')->constrained('entities')->restrictOnDelete();
            $table->foreignId('source_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->date('transfer_date');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(
                ['entity_id', 'source_warehouse_id', 'destination_warehouse_id'],
                'stock_transfers_location_index'
            );

            $table->index(
                ['item_id', 'transfer_date'],
                'stock_transfers_item_date_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
