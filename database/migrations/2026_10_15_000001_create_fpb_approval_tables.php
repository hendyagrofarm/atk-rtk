<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alur approval bertingkat untuk pengajuan custom (Formulir Pengajuan Barang).
     *
     * - positions          : master jabatan.
     * - approval_steps     : pengaturan urutan tahap approval per jabatan.
     * - request_approvals  : salinan alur untuk tiap pengajuan beserta hasilnya,
     *                        sehingga perubahan pengaturan tidak mengubah riwayat.
     */
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('position_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('positions')
                ->nullOnDelete();
        });

        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('step_order')->unique();
            $table->string('label', 100);
            $table->foreignId('position_id')
                ->constrained('positions')
                ->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')
                ->constrained('requests')
                ->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('label', 100);
            $table->foreignId('position_id')
                ->nullable()
                ->constrained('positions')
                ->nullOnDelete();
            $table->string('position_name', 150);
            $table->string('status', 20)->default('pending');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('acted_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['request_id', 'step_order']);
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->string('department', 150)->nullable()->after('note');
        });

        Schema::table('request_details', function (Blueprint $table) {
            $table->string('budget_type', 10)->nullable()->after('custom_category');
            $table->string('brand_type', 255)->nullable()->after('specification');
            $table->text('usage_purpose')->nullable()->after('brand_type');
        });
    }

    public function down(): void
    {
        Schema::table('request_details', function (Blueprint $table) {
            $table->dropColumn(['budget_type', 'brand_type', 'usage_purpose']);
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('department');
        });

        Schema::dropIfExists('request_approvals');
        Schema::dropIfExists('approval_steps');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('position_id');
        });

        Schema::dropIfExists('positions');
    }
};
