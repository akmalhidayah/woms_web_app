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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('tanggal_mulai_pekerjaan')->nullable()->after('progress_pekerjaan');
            $table->date('tanggal_selesai_pekerjaan')->nullable()->after('tanggal_mulai_pekerjaan');
        });

        Schema::table('initial_works', function (Blueprint $table) {
            $table->date('tanggal_mulai_pekerjaan')->nullable()->after('progress_pekerjaan');
            $table->date('tanggal_selesai_pekerjaan')->nullable()->after('tanggal_mulai_pekerjaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_mulai_pekerjaan',
                'tanggal_selesai_pekerjaan',
            ]);
        });

        Schema::table('initial_works', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_mulai_pekerjaan',
                'tanggal_selesai_pekerjaan',
            ]);
        });
    }
};
