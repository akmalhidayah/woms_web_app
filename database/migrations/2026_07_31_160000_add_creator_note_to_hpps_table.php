<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hpps', function (Blueprint $table): void {
            $table->text('creator_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hpps', function (Blueprint $table): void {
            $table->dropColumn('creator_note');
        });
    }
};
