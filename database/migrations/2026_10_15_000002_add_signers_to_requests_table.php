<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penandatangan FPB yang dapat diatur per pengajuan:
     * - PIC (Diajukan oleh)
     * - Atasan langsung PIC (Diketahui oleh)
     *
     * user_id tetap mencatat siapa yang membuat pengajuan di sistem
     * (misalnya admin yang mengisi atas nama PIC dari divisi lain).
     * Pengajuan lama dibiarkan NULL dan memakai data user pembuatnya.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('pic_name', 150)->nullable()->after('department');
            $table->string('pic_position', 150)->nullable()->after('pic_name');
            $table->string('superior_name', 150)->nullable()->after('pic_position');
            $table->string('superior_position', 150)->nullable()->after('superior_name');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['pic_name', 'pic_position', 'superior_name', 'superior_position']);
        });
    }
};
