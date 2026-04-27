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
        Schema::table('lhpp_basts', function (Blueprint $table) {
            $table->string('tipe_pekerjaan', 50)->nullable()->after('deskripsi_pekerjaan')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lhpp_basts', function (Blueprint $table) {
            $table->dropColumn('tipe_pekerjaan');
        });
    }
};
