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
        Schema::table('items', function (Blueprint $table) {
            $table->enum('type', ['ATK', 'RTK'])
                ->default('ATK')
                ->after('name');

            $table->string('storage_location')
                ->nullable()
                ->after('current_stock');

            $table->text('description')
                ->nullable()
                ->after('storage_location');
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'storage_location',
                'description',
            ]);
        });
    }
};