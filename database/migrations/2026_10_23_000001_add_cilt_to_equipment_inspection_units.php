<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_inspection_units', function (Blueprint $table): void {
            $table->string('cilt_path')->nullable();
            $table->string('cilt_original_name')->nullable();
            $table->unsignedInteger('cilt_size')->nullable();
            $table->timestamp('cilt_updated_at')->nullable();
            $table->foreignId('cilt_uploaded_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment_inspection_units', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cilt_uploaded_by');
            $table->dropColumn(['cilt_path', 'cilt_original_name', 'cilt_size', 'cilt_updated_at']);
        });
    }
};
