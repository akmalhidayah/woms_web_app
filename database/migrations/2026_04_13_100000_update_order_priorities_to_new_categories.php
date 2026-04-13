<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('orders')->where('prioritas', 'urgent')->update([
            'prioritas' => 'emergency_unplan_overhaul',
        ]);

        DB::table('orders')->where('prioritas', 'tinggi')->update([
            'prioritas' => 'emergency_lte_7_hari',
        ]);

        DB::table('orders')->where('prioritas', 'sedang')->update([
            'prioritas' => 'high_gt_7_sd_10_hari',
        ]);

        DB::table('orders')->where('prioritas', 'rendah')->update([
            'prioritas' => 'medium_gt_10_hari',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('orders')->where('prioritas', 'emergency_unplan_overhaul')->update([
            'prioritas' => 'urgent',
        ]);

        DB::table('orders')->where('prioritas', 'emergency_lte_7_hari')->update([
            'prioritas' => 'tinggi',
        ]);

        DB::table('orders')->where('prioritas', 'high_gt_7_sd_10_hari')->update([
            'prioritas' => 'sedang',
        ]);

        DB::table('orders')->where('prioritas', 'medium_gt_10_hari')->update([
            'prioritas' => 'rendah',
        ]);
    }
};
