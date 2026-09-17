<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountPphPercentToSpbPurchaseOrders extends Migration
{
    public function up()
    {
        Schema::table('spb_purchase_orders', function (Blueprint $table) {
            // Persentase discount & PPh yang diisi manual oleh Purchasing sebelum PO di-print,
            // dipakai di recap kecil sejajar area tanda tangan pada preview PO.
            if (!Schema::hasColumn('spb_purchase_orders', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->nullable()->default(0);
            }
            if (!Schema::hasColumn('spb_purchase_orders', 'pph_percent')) {
                $table->decimal('pph_percent', 5, 2)->nullable()->default(0);
            }
        });
    }

    public function down()
    {
        Schema::table('spb_purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('spb_purchase_orders', 'discount_percent')) {
                $table->dropColumn('discount_percent');
            }
            if (Schema::hasColumn('spb_purchase_orders', 'pph_percent')) {
                $table->dropColumn('pph_percent');
            }
        });
    }
}