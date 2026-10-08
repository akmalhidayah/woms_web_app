<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outline_agreement_monthly_realizations', function (Blueprint $table): void {
            $table->unsignedInteger('estimator_completed_orders')->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('outline_agreement_monthly_realizations', function (Blueprint $table): void {
            $table->dropColumn('estimator_completed_orders');
        });
    }
};
