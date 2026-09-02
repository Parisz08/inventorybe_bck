<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDitinjauDiajukanForPermintaanVendor extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('spb', 'ditinjau_oleh')) {
            Schema::table('spb', function (Blueprint $table) {
                // Nama Manager Dept. yang meninjau SPPB. Diisi wajib saat SPPB dibuat.
                $table->string('ditinjau_oleh')->nullable();
            });
        }

        if (!Schema::hasColumn('spb_purchase_orders', 'diajukan_oleh')) {
            Schema::table('spb_purchase_orders', function (Blueprint $table) {
                // Nama Manager Dept. yang mengajukan PO. Diisi wajib saat PO diterbitkan.
                $table->string('diajukan_oleh')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('spb', 'ditinjau_oleh')) {
            Schema::table('spb', function (Blueprint $table) {
                $table->dropColumn('ditinjau_oleh');
            });
        }
        if (Schema::hasColumn('spb_purchase_orders', 'diajukan_oleh')) {
            Schema::table('spb_purchase_orders', function (Blueprint $table) {
                $table->dropColumn('diajukan_oleh');
            });
        }
    }
}
