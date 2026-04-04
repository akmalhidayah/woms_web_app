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
        if (Schema::hasColumn('orders', 'catatan_status')) {
            return;
        }

        $afterColumn = Schema::hasColumn('orders', 'catatan') ? 'catatan' : null;

        Schema::table('orders', function (Blueprint $table) use ($afterColumn) {
            $column = $table->string('catatan_status')->default('pending');

            if ($afterColumn !== null) {
                $column->after($afterColumn);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'catatan_status')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('catatan_status');
        });
    }
};
