<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_workshops', function (Blueprint $table): void {
            $table->string('pic_user')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_workshops', function (Blueprint $table): void {
            $table->dropColumn('pic_user');
        });
    }
};
