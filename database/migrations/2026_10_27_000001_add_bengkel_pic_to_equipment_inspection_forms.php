<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_inspection_forms', function (Blueprint $table): void {
            $table->foreignId('bengkel_pic_id')
                ->nullable()
                ->after('description')
                ->constrained('bengkel_pics')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment_inspection_forms', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('bengkel_pic_id');
        });
    }
};
