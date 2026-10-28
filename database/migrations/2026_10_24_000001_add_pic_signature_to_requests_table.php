<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanda tangan digital PIC (Diajukan oleh) pada FPB.
     *
     * - pic_user_id        : user di sistem yang menjadi PIC (staff pembuat sendiri, atau user
     *                        yang dipilih admin). NULL bila PIC tidak punya akun (tanda tangan basah).
     * - pic_signature_path : salinan tanda tangan PIC saat FPB dibuat, agar FPB tidak berubah
     *                        bila tanda tangan user diganti kemudian.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->foreignId('pic_user_id')
                ->nullable()
                ->after('superior_user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('pic_signature_path')->nullable()->after('pic_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pic_user_id');
            $table->dropColumn('pic_signature_path');
        });
    }
};
