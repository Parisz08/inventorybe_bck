<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSequenceCountersAndTaxAuditColumns extends Migration
{
    public function up()
    {
        // Tabel counter atomik untuk generate nomor SPPB/PO tanpa race condition.
        // Pakai pola "INSERT ... ON DUPLICATE KEY UPDATE counter = LAST_INSERT_ID(counter + 1)"
        // yang aman dari 2 request bersamaan (row-level lock otomatis dari MySQL).
        if (!Schema::hasTable('sequence_counters')) {
            Schema::create('sequence_counters', function (Blueprint $table) {
                $table->string('seq_key', 191)->primary();
                $table->unsignedInteger('counter')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('spb_purchase_orders', function (Blueprint $table) {
            // Jejak siapa & kapan terakhir ubah Discount/PPN/PPh, buat audit trail.
            if (!Schema::hasColumn('spb_purchase_orders', 'tax_updated_by')) {
                $table->string('tax_updated_by')->nullable()->after('no_sppb_manual');
            }
            if (!Schema::hasColumn('spb_purchase_orders', 'tax_updated_at')) {
                $table->timestamp('tax_updated_at')->nullable()->after('tax_updated_by');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('sequence_counters');

        Schema::table('spb_purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('spb_purchase_orders', 'tax_updated_by')) {
                $table->dropColumn('tax_updated_by');
            }
            if (Schema::hasColumn('spb_purchase_orders', 'tax_updated_at')) {
                $table->dropColumn('tax_updated_at');
            }
        });
    }
}