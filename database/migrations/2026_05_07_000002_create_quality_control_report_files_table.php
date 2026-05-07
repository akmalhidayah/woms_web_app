<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_control_report_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quality_control_report_id')
                ->constrained('quality_control_reports')
                ->cascadeOnDelete();
            $table->string('category', 80)->index();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_control_report_files');
    }
};
