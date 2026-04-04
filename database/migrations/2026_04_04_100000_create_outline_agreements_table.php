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
        Schema::create('outline_agreements', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_oa')->unique();
            $table->foreignId('unit_work_id')->constrained('unit_works');
            $table->string('jenis_kontrak');
            $table->string('nama_kontrak');
            $table->decimal('nilai_kontrak_awal', 18, 2);
            $table->date('periode_awal_start');
            $table->date('periode_awal_end');
            $table->decimal('current_total_nilai', 18, 2);
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->unsignedBigInteger('latest_history_id')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('status');
            $table->index('current_period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outline_agreements');
    }
};
