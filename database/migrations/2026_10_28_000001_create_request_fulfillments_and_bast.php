<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Riwayat tahap pembelanjaan sampai barang diterima (satu baris per tahap).
        if (! Schema::hasTable('request_fulfillments')) {
            Schema::create('request_fulfillments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
                $table->string('stage', 30);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('vendor')->nullable();
                $table->string('po_number', 100)->nullable();
                $table->date('eta_date')->nullable();
                $table->text('note')->nullable();
                $table->timestamp('happened_at')->nullable();
                $table->timestamps();

                $table->unique(['request_id', 'stage']);
            });
        }

        Schema::table('requests', function (Blueprint $table) {
            if (! Schema::hasColumn('requests', 'bast_number')) {
                $table->string('bast_number', 40)->nullable()->unique()->after('approved_at');
                $table->text('bast_note')->nullable()->after('bast_number');
                $table->foreignId('handed_over_by')->nullable()->after('bast_note')->constrained('users')->nullOnDelete();
                $table->string('handed_signature_path')->nullable()->after('handed_over_by');
                $table->string('received_name')->nullable()->after('handed_signature_path');
                $table->foreignId('received_user_id')->nullable()->after('received_name')->constrained('users')->nullOnDelete();
                $table->string('received_signature_path')->nullable()->after('received_user_id');
                $table->boolean('received_on_behalf')->default(false)->after('received_signature_path');
                $table->timestamp('received_at')->nullable()->after('received_on_behalf');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['handed_over_by']);
            $table->dropForeign(['received_user_id']);
            $table->dropUnique(['bast_number']);
            $table->dropColumn([
                'bast_number', 'bast_note', 'handed_over_by', 'handed_signature_path',
                'received_name', 'received_user_id', 'received_signature_path',
                'received_on_behalf', 'received_at',
            ]);
        });

        Schema::dropIfExists('request_fulfillments');
    }
};
