<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->engine     = 'InnoDB';
            $table->charset    = 'utf8mb4';
            $table->collation  = 'utf8mb4_general_ci';
            $table->increments('id');

            // Penerima notifikasi. 1 baris = 1 notifikasi untuk 1 user, supaya status
            // "sudah dibaca" independen per orang walau event sumbernya sama
            // (misal 1 SPPB baru masuk dibaca ke SEMUA Admin, masing-masing baris sendiri).
            $table->unsignedInteger('user_id');

            // Jenis event, dipakai FE buat pilih ikon/warna. Contoh:
            // spb_created, spb_updated, spb_cancelled, spb_approved, spb_rejected,
            // po_issued, po_received, po_invoiced, po_paid, spb_selesai
            $table->string('type', 50)->nullable();

            $table->string('title');
            $table->text('message')->nullable();

            // Referensi ke dokumen terkait, dipakai FE buat langsung buka detailnya.
            $table->unsignedInteger('spb_id')->nullable();
            $table->unsignedInteger('po_id')->nullable();

            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            // Sengaja TIDAK pakai foreign key keras ke tabel users: tipe kolom `id` di
            // tabel users bisa beda-beda antar setup project (int vs bigint, signed vs
            // unsigned, dll) dan bikin error "Foreign key constraint is incorrectly
            // formed" kalau tidak persis sama. Index biasa sudah cukup untuk performa
            // query, dan integritas datanya sudah dijaga di level aplikasi (selalu insert
            // user_id dari user yang benar-benar login / role yang valid).
            $table->foreign('spb_id')->references('id')->on('spb')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}