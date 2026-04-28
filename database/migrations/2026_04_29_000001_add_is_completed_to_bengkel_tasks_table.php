<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bengkel_tasks', function (Blueprint $table): void {
            $table->boolean('is_completed')->default(false)->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('bengkel_tasks', function (Blueprint $table): void {
            $table->dropColumn('is_completed');
        });
    }
};
