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
        Schema::table('requests', function (Blueprint $table) {
            if (! Schema::hasColumn('requests', 'entity_id')) {
                $table->foreignId('entity_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('entities')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            }

            if (! Schema::hasColumn('requests', 'approver_note')) {
                $table->text('approver_note')
                    ->nullable()
                    ->after('note');
            }
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'entity_id')) {
                $table->dropConstrainedForeignId('entity_id');
            }

            if (Schema::hasColumn('requests', 'approver_note')) {
                $table->dropColumn('approver_note');
            }
        });
    }
};