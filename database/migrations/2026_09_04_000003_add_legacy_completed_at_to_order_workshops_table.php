<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_workshops', function (Blueprint $table): void {
            $table->dateTime('legacy_completed_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_workshops', function (Blueprint $table): void {
            $table->dropColumn('legacy_completed_at');
        });
    }
};
