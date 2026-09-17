<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPpnPercentToSpbPurchaseOrders extends Migration
{
    public function up()
    {
        Schema::table('spb_purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('spb_purchase_orders', 'ppn_percent')) {
                $table->decimal('ppn_percent', 5, 2)->nullable()->default(null)->after('discount_percent');
            }
        });

        // discount_percent & pph_percent sebelumnya default 0 (bukan NULL), sehingga
        // tidak bisa dibedakan antara "belum pernah diisi" vs "sengaja diisi 0".
        // Diubah jadi default NULL, dan baris yang masih 0 bawaan (belum pernah
        // disimpan manual) dikosongkan lagi.
        Schema::table('spb_purchase_orders', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->nullable()->default(null)->change();
            $table->decimal('pph_percent', 5, 2)->nullable()->default(null)->change();
        });

        DB::table('spb_purchase_orders')->where('discount_percent', 0)->update(['discount_percent' => null]);
        DB::table('spb_purchase_orders')->where('pph_percent', 0)->update(['pph_percent' => null]);
    }

    public function down()
    {
        Schema::table('spb_purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('spb_purchase_orders', 'ppn_percent')) {
                $table->dropColumn('ppn_percent');
            }
        });
    }
}