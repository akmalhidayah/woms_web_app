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
        Schema::table('hpps', function (Blueprint $table) {
            if (! Schema::hasColumn('hpps', 'outline_agreement_id')) {
                $table->foreignId('outline_agreement_id')
                    ->nullable()
                    ->after('order_id')
                    ->constrained('outline_agreements')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('hpps', 'unit_work_id')) {
                $table->foreignId('unit_work_id')
                    ->nullable()
                    ->after('unit_kerja')
                    ->constrained('unit_works')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hpps', function (Blueprint $table) {
            if (Schema::hasColumn('hpps', 'outline_agreement_id')) {
                $table->dropConstrainedForeignId('outline_agreement_id');
            }

            if (Schema::hasColumn('hpps', 'unit_work_id')) {
                $table->dropConstrainedForeignId('unit_work_id');
            }
        });
    }
};
