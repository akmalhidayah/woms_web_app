<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('garansis', function (Blueprint $table): void {
            $table->unsignedBigInteger('lhpp_bast_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('garansis', function (Blueprint $table): void {
            $table->unsignedBigInteger('lhpp_bast_id')->nullable(false)->change();
        });
    }
};
