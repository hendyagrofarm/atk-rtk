<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'nik')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nik', 30)->nullable()->after('email');
            });
        }

        // Data formulir BAST aset yang diisi admin saat serah terima.
        if (! Schema::hasColumn('requests', 'bast_date')) {
            Schema::table('requests', function (Blueprint $table) {
                $table->date('bast_date')->nullable();
                $table->string('bast_place', 80)->nullable();
                $table->string('bast_first_name', 150)->nullable();
                $table->string('bast_first_nik', 30)->nullable();
                $table->string('bast_first_position', 150)->nullable();
                $table->string('bast_second_name', 150)->nullable();
                $table->string('bast_second_nik', 30)->nullable();
                $table->string('bast_second_position', 150)->nullable();
                $table->string('bast_known_name', 150)->nullable();
                $table->string('bast_known_position', 150)->nullable();
            });
        }

        if (! Schema::hasColumn('request_details', 'asset_code')) {
            Schema::table('request_details', function (Blueprint $table) {
                $table->string('bast_name')->nullable();
                $table->text('bast_specification')->nullable();
                $table->string('asset_code', 100)->nullable();
                $table->unsignedInteger('bast_quantity')->nullable();
                $table->string('bast_condition', 100)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('request_details', function (Blueprint $table) {
            $table->dropColumn(['bast_name', 'bast_specification', 'asset_code', 'bast_quantity', 'bast_condition']);
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn([
                'bast_date', 'bast_place', 'bast_first_name', 'bast_first_nik', 'bast_first_position',
                'bast_second_name', 'bast_second_nik', 'bast_second_position', 'bast_known_name', 'bast_known_position',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nik');
        });
    }
};
