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
        Schema::table('initial_work_signatures', function (Blueprint $table) {
            $table->text('token_encrypted')->nullable()->after('token_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('initial_work_signatures', function (Blueprint $table) {
            $table->dropColumn('token_encrypted');
        });
    }
};
