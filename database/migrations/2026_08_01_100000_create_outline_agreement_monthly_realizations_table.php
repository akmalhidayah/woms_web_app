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
        $tableName = 'outline_agreement_monthly_realizations';

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->foreignId('outline_agreement_id');
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month');
                $table->unsignedBigInteger('pr_po_amount')->default(0);
                $table->unsignedBigInteger('urgent_amount')->default(0);
                $table->timestamps();

                $table->foreign('outline_agreement_id', 'oa_monthly_realizations_oa_fk')
                    ->references('id')
                    ->on('outline_agreements')
                    ->cascadeOnDelete();
                $table->unique(
                    ['outline_agreement_id', 'year', 'month'],
                    'oa_monthly_realizations_period_unique'
                );
                $table->index(['year', 'month'], 'oa_monthly_realizations_period_index');
            });

            return;
        }

        $hasOutlineAgreementForeignKey = collect(Schema::getForeignKeys($tableName))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['outline_agreement_id']);

        if (! $hasOutlineAgreementForeignKey) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('outline_agreement_id', 'oa_monthly_realizations_oa_fk')
                    ->references('id')
                    ->on('outline_agreements')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasIndex($tableName, 'oa_monthly_realizations_period_unique')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unique(
                    ['outline_agreement_id', 'year', 'month'],
                    'oa_monthly_realizations_period_unique'
                );
            });
        }

        if (! Schema::hasIndex($tableName, 'oa_monthly_realizations_period_index')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index(['year', 'month'], 'oa_monthly_realizations_period_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outline_agreement_monthly_realizations');
    }
};
