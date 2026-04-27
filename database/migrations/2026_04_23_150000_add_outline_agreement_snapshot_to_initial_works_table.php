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
        Schema::table('initial_works', function (Blueprint $table) {
            $table->foreignId('outline_agreement_id')
                ->nullable()
                ->after('order_id')
                ->constrained('outline_agreements')
                ->nullOnDelete();
            $table->foreignId('unit_work_id')
                ->nullable()
                ->after('outline_agreement_id')
                ->constrained('unit_works')
                ->nullOnDelete();
            $table->foreignId('unit_work_section_id')
                ->nullable()
                ->after('unit_work_id')
                ->constrained('unit_work_sections')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('initial_works', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_work_section_id');
            $table->dropConstrainedForeignId('unit_work_id');
            $table->dropConstrainedForeignId('outline_agreement_id');
        });
    }
};
