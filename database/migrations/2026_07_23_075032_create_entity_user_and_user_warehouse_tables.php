<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel akses multi-entitas dan multi-gudang.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Tabel pivot akses user ke entitas
        |--------------------------------------------------------------------------
        */
        if (! Schema::hasTable('entity_user')) {
            Schema::create('entity_user', function (Blueprint $table) {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreignId('entity_id')
                    ->constrained('entities')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->timestamps();

                $table->unique(
                    [
                        'user_id',
                        'entity_id',
                    ],
                    'entity_user_user_entity_unique'
                );

                $table->index(
                    'entity_id',
                    'entity_user_entity_id_index'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Tabel pivot akses user ke gudang
        |--------------------------------------------------------------------------
        */
        if (! Schema::hasTable('user_warehouse')) {
            Schema::create('user_warehouse', function (Blueprint $table) {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreignId('warehouse_id')
                    ->constrained('warehouses')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->timestamps();

                $table->unique(
                    [
                        'user_id',
                        'warehouse_id',
                    ],
                    'user_warehouse_user_warehouse_unique'
                );

                $table->index(
                    'warehouse_id',
                    'user_warehouse_warehouse_id_index'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Salin data akses lama
        |--------------------------------------------------------------------------
        | entity_id dan warehouse_id pada users tetap dipertahankan sebagai
        | lokasi default. Data tersebut juga disalin ke tabel pivot.
        */
        $users = DB::table('users')
            ->select([
                'id',
                'role',
                'entity_id',
                'warehouse_id',
            ])
            ->get();

        foreach ($users as $user) {
            /*
            |--------------------------------------------------------------------------
            | Super Admin tidak memerlukan baris pivot
            |--------------------------------------------------------------------------
            | Super Admin selalu dianggap memiliki akses semua entitas dan gudang.
            */
            if ($user->role === 'super_admin') {
                continue;
            }

            if ($user->entity_id) {
                DB::table('entity_user')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'entity_id' => $user->entity_id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            if ($user->warehouse_id) {
                DB::table('user_warehouse')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'warehouse_id' => $user->warehouse_id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Menghapus tabel pivot.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_warehouse');
        Schema::dropIfExists('entity_user');
    }
};