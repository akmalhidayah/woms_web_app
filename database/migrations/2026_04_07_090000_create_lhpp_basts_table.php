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
        Schema::create('lhpp_basts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('termin_type', 20)->default('termin_1')->index();
            $table->foreignId('parent_lhpp_bast_id')->nullable()->constrained('lhpp_basts')->nullOnDelete();
            $table->foreignId('hpp_id')->nullable()->constrained('hpps')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('nomor_order')->index();
            $table->string('notifikasi')->nullable()->index();
            $table->string('purchase_order_number')->nullable()->index();
            $table->string('deskripsi_pekerjaan')->nullable();
            $table->string('unit_kerja')->nullable()->index();
            $table->string('seksi')->nullable();
            $table->date('tanggal_bast')->index();
            $table->date('tanggal_mulai_pekerjaan')->nullable();
            $table->date('tanggal_selesai_pekerjaan')->nullable();
            $table->string('approval_threshold', 20)->default('under_250');
            $table->decimal('nilai_hpp', 15, 2)->default(0);
            $table->json('material_items')->nullable();
            $table->json('service_items')->nullable();
            $table->decimal('subtotal_material', 15, 2)->default(0);
            $table->decimal('subtotal_jasa', 15, 2)->default(0);
            $table->decimal('total_aktual_biaya', 15, 2)->default(0);
            $table->decimal('termin_1_nilai', 15, 2)->default(0);
            $table->decimal('termin_2_nilai', 15, 2)->default(0);
            $table->string('termin1_status', 20)->default('belum')->index();
            $table->string('termin2_status', 20)->default('belum')->index();
            $table->string('quality_control_status', 20)->default('pending')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'termin_type'], 'lhpp_basts_order_termin_unique');
        });

        Schema::create('lhpp_bast_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lhpp_bast_id')->constrained('lhpp_basts')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lhpp_bast_images');
        Schema::dropIfExists('lhpp_basts');
    }
};
