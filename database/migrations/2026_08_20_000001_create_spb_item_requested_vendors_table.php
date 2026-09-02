<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpbItemRequestedVendorsTable extends Migration
{
    public function up()
    {
        Schema::create('spb_item_requested_vendors', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id');
            $table->unsignedInteger('spb_item_id');
            $table->unsignedInteger('vendor_id');
            $table->string('requested_by')->nullable();
            $table->timestamps();

            $table->foreign('spb_item_id')->references('id')->on('spb_items')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->unique(['spb_item_id', 'vendor_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('spb_item_requested_vendors');
    }
}