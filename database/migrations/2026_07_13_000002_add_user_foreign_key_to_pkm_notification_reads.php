<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pkm_notification_reads')->whereNotExists(function ($query): void {
            $query->selectRaw('1')->from('users')->whereColumn('users.id', 'pkm_notification_reads.user_id');
        })->delete();

        Schema::table('pkm_notification_reads', function (Blueprint $table): void {
            $table->foreign('user_id', 'pkm_notification_reads_user_fk')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pkm_notification_reads', function (Blueprint $table): void {
            $table->dropForeign('pkm_notification_reads_user_fk');
        });
    }
};
