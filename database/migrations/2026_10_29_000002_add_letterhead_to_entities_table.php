<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('entities', 'letterhead_path')) {
            Schema::table('entities', function (Blueprint $table) {
                $table->string('letterhead_path')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('entities', function (Blueprint $table) {
            $table->dropColumn('letterhead_path');
        });
    }
};
