<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_order')->unique();
            $table->string('notifikasi')->nullable()->unique();
            $table->string('nama_pekerjaan');
            $table->string('unit_kerja');
            $table->string('seksi');
            $table->text('deskripsi');
            $table->string('prioritas');
            $table->date('tanggal_order');
            $table->date('target_selesai');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
