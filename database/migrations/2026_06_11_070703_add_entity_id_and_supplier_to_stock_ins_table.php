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
        Schema::table('stock_ins', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_ins', 'entity_id')) {
                $table->foreignId('entity_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('entities')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            }

            if (! Schema::hasColumn('stock_ins', 'supplier')) {
                $table->string('supplier')
                    ->nullable()
                    ->after('date');
            }
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::table('stock_ins', function (Blueprint $table) {
            if (Schema::hasColumn('stock_ins', 'entity_id')) {
                $table->dropConstrainedForeignId('entity_id');
            }

            if (Schema::hasColumn('stock_ins', 'supplier')) {
                $table->dropColumn('supplier');
            }
        });
    }
};