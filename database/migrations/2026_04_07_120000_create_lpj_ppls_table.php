<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lpj_ppls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lhpp_bast_id')->unique()->constrained('lhpp_basts')->cascadeOnDelete();
            $table->string('lpj_number_termin1')->nullable();
            $table->string('ppl_number_termin1')->nullable();
            $table->string('lpj_document_path_termin1')->nullable();
            $table->string('ppl_document_path_termin1')->nullable();
            $table->string('lpj_number_termin2')->nullable();
            $table->string('ppl_number_termin2')->nullable();
            $table->string('lpj_document_path_termin2')->nullable();
            $table->string('ppl_document_path_termin2')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lpj_ppls');
    }
};
