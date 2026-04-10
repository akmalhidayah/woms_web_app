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
        Schema::create('fabrication_construction_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->index();
            $table->string('jenis_item')->index();
            $table->string('sub_jenis_item')->nullable()->index();
            $table->string('kategori_item')->nullable()->index();
            $table->string('nama_item');
            $table->string('satuan', 50);
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fabrication_construction_contracts');
    }
};
