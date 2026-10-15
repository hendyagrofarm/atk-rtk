<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mencatat kondisi stok saat pengajuan dibuat untuk kebutuhan laporan KPI.
     *
     * Pengajuan lama dibiarkan NULL karena stok pada saat itu tidak diketahui.
     */
    public function up(): void
    {
        Schema::table('request_details', function (Blueprint $table) {
            $table->integer('stock_at_request')
                ->nullable()
                ->after('quantity');

            $table->integer('shortage_quantity')
                ->nullable()
                ->after('stock_at_request');
        });
    }

    public function down(): void
    {
        Schema::table('request_details', function (Blueprint $table) {
            $table->dropColumn(['stock_at_request', 'shortage_quantity']);
        });
    }
};
