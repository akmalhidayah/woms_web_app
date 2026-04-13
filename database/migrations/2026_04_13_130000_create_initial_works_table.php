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
        Schema::create('initial_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('nomor_initial_work')->unique();
            $table->string('nomor_order')->index();
            $table->string('notifikasi')->nullable();
            $table->string('nama_pekerjaan');
            $table->string('unit_kerja')->nullable();
            $table->string('seksi')->nullable();
            $table->string('kepada_yth')->nullable();
            $table->string('perihal');
            $table->date('tanggal_initial_work');
            $table->json('functional_location');
            $table->json('scope_pekerjaan');
            $table->json('qty');
            $table->json('stn');
            $table->json('keterangan')->nullable();
            $table->text('keterangan_pekerjaan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initial_works');
    }
};
