<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNeededDateToSpbTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('spb', 'needed_date')) {
            Schema::table('spb', function (Blueprint $table) {
                // Tanggal barang dibutuhkan, diisi manual saat SPPB dibuat.
                $table->date('needed_date')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('spb', 'needed_date')) {
            Schema::table('spb', function (Blueprint $table) {
                $table->dropColumn('needed_date');
            });
        }
    }
}