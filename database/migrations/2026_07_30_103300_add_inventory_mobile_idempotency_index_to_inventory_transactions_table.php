<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->unique(
                ['inventory_user_id', 'reference_number'],
                'inventory_transactions_mobile_idempotency_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropUnique('inventory_transactions_mobile_idempotency_unique');
        });
    }
};
