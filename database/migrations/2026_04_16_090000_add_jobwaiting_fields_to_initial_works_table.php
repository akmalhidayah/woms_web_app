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
        Schema::table('initial_works', function (Blueprint $table) {
            $table->date('target_penyelesaian')->nullable()->after('tanggal_initial_work');
            $table->unsignedTinyInteger('progress_pekerjaan')->default(0)->after('target_penyelesaian');
            $table->text('vendor_note')->nullable()->after('keterangan_pekerjaan');
            $table->text('admin_note')->nullable()->after('vendor_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('initial_works', function (Blueprint $table) {
            $table->dropColumn([
                'target_penyelesaian',
                'progress_pekerjaan',
                'vendor_note',
                'admin_note',
            ]);
        });
    }
};
