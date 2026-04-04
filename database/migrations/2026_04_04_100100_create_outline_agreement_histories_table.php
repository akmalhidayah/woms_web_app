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
        if (! Schema::hasTable('outline_agreement_histories')) {
            Schema::create('outline_agreement_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outline_agreement_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('revision_no');
                $table->string('tipe_perubahan', 32);
                $table->decimal('nilai_tambahan', 18, 2)->default(0);
                $table->date('periode_start')->nullable();
                $table->date('periode_end')->nullable();
                $table->decimal('snapshot_total_nilai', 18, 2);
                $table->date('snapshot_period_start');
                $table->date('snapshot_period_end');
                $table->text('keterangan')->nullable();
                $table->json('payload_json')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();
            });
        }

        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'outline_agreement_histories')
            ->where('index_name', 'oa_histories_oa_rev_unique')
            ->exists();

        if (! $indexExists) {
            Schema::table('outline_agreement_histories', function (Blueprint $table) {
                $table->unique(['outline_agreement_id', 'revision_no'], 'oa_histories_oa_rev_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outline_agreement_histories');
    }
};
