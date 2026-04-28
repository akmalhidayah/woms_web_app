<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('garansis', 'order_id')) {
            Schema::table('garansis', function (Blueprint $table) {
                $table->foreignId('order_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('orders')
                    ->cascadeOnDelete();

                $table->unique('order_id');
            });
        }

        DB::table('garansis')
            ->join('lhpp_basts', 'garansis.lhpp_bast_id', '=', 'lhpp_basts.id')
            ->whereNull('garansis.order_id')
            ->update(['garansis.order_id' => DB::raw('lhpp_basts.order_id')]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE garansis MODIFY lhpp_bast_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        DB::table('garansis')->whereNull('lhpp_bast_id')->delete();

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE garansis MODIFY lhpp_bast_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('garansis', 'order_id')) {
            Schema::table('garansis', function (Blueprint $table) {
                $table->dropUnique(['order_id']);
                $table->dropConstrainedForeignId('order_id');
            });
        }
    }
};
