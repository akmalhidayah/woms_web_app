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
        Schema::create('outline_agreement_monthly_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outline_agreement_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedBigInteger('pr_po_amount')->default(0);
            $table->unsignedBigInteger('urgent_amount')->default(0);
            $table->timestamps();

            $table->unique(
                ['outline_agreement_id', 'year', 'month'],
                'oa_monthly_realizations_period_unique'
            );
            $table->index(['year', 'month'], 'oa_monthly_realizations_period_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outline_agreement_monthly_realizations');
    }
};
