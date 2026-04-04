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
        Schema::create('hpps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('nomor_order')->index();
            $table->string('nama_pekerjaan');
            $table->string('unit_kerja');
            $table->string('cost_centre')->nullable();
            $table->string('kategori_pekerjaan', 50);
            $table->string('area_pekerjaan', 50);
            $table->string('nilai_hpp_bucket', 20)->default('under');
            $table->string('unit_kerja_pengendali')->nullable();
            $table->string('outline_agreement')->nullable();
            $table->string('periode_outline_agreement')->nullable();
            $table->string('approval_case')->nullable();
            $table->json('approval_flow')->nullable();
            $table->json('item_groups')->nullable();
            $table->decimal('total_keseluruhan', 15, 2)->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hpps');
    }
};
