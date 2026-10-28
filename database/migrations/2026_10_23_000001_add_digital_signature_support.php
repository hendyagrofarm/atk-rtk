<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanda tangan digital dan atasan langsung sebagai tahap approval.
     *
     * - users.signature_path             : file PNG tanda tangan user (disk privat).
     * - requests.superior_user_id        : atasan langsung yang dipilih dari user sistem.
     * - request_approvals.assigned_user_id : tahap yang ditunjuk ke user tertentu
     *                                        (atasan langsung), bukan berdasarkan jabatan.
     * - request_approvals.signature_path : salinan tanda tangan saat tahap disetujui,
     *                                      agar FPB lama tidak berubah bila tanda tangan diganti.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('position_id');
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->foreignId('superior_user_id')
                ->nullable()
                ->after('superior_position')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('request_approvals', function (Blueprint $table) {
            $table->foreignId('assigned_user_id')
                ->nullable()
                ->after('position_name')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('signature_path')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('request_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropColumn('signature_path');
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('superior_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
    }
};
