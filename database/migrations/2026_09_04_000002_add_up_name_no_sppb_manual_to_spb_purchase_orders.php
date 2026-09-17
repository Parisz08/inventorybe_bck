<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUpNameNoSppbManualToSpbPurchaseOrders extends Migration
{
    public function up()
    {
        Schema::table('spb_purchase_orders', function (Blueprint $table) {
            // Nama "Up" & No. SPPB manual, buat override nilai otomatis (sign_dibuat / spb.no_spb)
            // di preview print PO kalau Purchasing mau isi beda.
            if (!Schema::hasColumn('spb_purchase_orders', 'up_name')) {
                $table->string('up_name')->nullable()->after('pph_percent');
            }
            if (!Schema::hasColumn('spb_purchase_orders', 'no_sppb_manual')) {
                $table->string('no_sppb_manual')->nullable()->after('up_name');
            }
        });
    }

    public function down()
    {
        Schema::table('spb_purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('spb_purchase_orders', 'up_name')) {
                $table->dropColumn('up_name');
            }
            if (Schema::hasColumn('spb_purchase_orders', 'no_sppb_manual')) {
                $table->dropColumn('no_sppb_manual');
            }
        });
    }
}