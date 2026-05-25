<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bengkel_tasks', function (Blueprint $table): void {
            $table->text('pending_reason')->nullable()->after('progress_status');
        });
    }

    public function down(): void
    {
        Schema::table('bengkel_tasks', function (Blueprint $table): void {
            $table->dropColumn('pending_reason');
        });
    }
};
