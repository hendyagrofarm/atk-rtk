<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alasan penolakan terstruktur untuk laporan KPI.
     * Pengajuan yang sudah ditolak sebelumnya dibiarkan NULL.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('rejection_reason', 50)
                ->nullable()
                ->after('approver_note');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
