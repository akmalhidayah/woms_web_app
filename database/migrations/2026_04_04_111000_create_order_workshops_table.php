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
        Schema::create('order_workshops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('konfirmasi_anggaran')->nullable();
            $table->text('keterangan_konfirmasi')->nullable();
            $table->string('status_anggaran')->nullable();
            $table->text('keterangan_anggaran')->nullable();
            $table->string('status_material')->nullable();
            $table->text('keterangan_material')->nullable();
            $table->string('progress_status')->nullable();
            $table->text('keterangan_progress')->nullable();
            $table->text('catatan')->nullable();
            $table->string('nomor_e_korin')->nullable();
            $table->string('status_e_korin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_workshops');
    }
};
