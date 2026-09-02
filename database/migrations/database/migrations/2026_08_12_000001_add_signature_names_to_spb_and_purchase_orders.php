<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSignatureNamesToSpbAndPurchaseOrders extends Migration
{
    public function up()
    {
        Schema::table('spb', function (Blueprint $table) {
            $table->string('sign_diajukan')->nullable()->after('created_by');
            $table->string('sign_ditinjau')->nullable()->after('sign_diajukan');
            $table->string('sign_disetujui')->nullable()->after('sign_ditinjau');
        });

        Schema::table('spb_purchase_orders', function (Blueprint $table) {
            $table->string('sign_dibuat')->nullable()->after('status');
            $table->string('sign_diajukan')->nullable()->after('sign_dibuat');
            $table->string('sign_disetujui')->nullable()->after('sign_diajukan');
        });
    }

    public function down()
    {
        Schema::table('spb', function (Blueprint $table) {
            $table->dropColumn(['sign_diajukan', 'sign_ditinjau', 'sign_disetujui']);
        });

        Schema::table('spb_purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['sign_dibuat', 'sign_diajukan', 'sign_disetujui']);
        });
    }
}