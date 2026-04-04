<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('outline_agreement_targets')) {
            Schema::create('outline_agreement_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outline_agreement_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('tahun');
                $table->decimal('nilai_target', 18, 2);
                $table->timestamps();
            });
        }

        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'outline_agreement_targets')
            ->where('index_name', 'oa_targets_oa_year_unique')
            ->exists();

        if (! $indexExists) {
            Schema::table('outline_agreement_targets', function (Blueprint $table) {
                $table->unique(['outline_agreement_id', 'tahun'], 'oa_targets_oa_year_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outline_agreement_targets');
    }
};
