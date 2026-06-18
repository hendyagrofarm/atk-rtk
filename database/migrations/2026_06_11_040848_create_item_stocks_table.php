<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        Schema::create('item_stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entity_id')
                ->constrained('entities')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->integer('current_stock')->default(0);
            $table->integer('minimum_stock')->default(0);

            $table->timestamps();

            $table->unique(['entity_id', 'item_id']);
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_stocks');
    }
};