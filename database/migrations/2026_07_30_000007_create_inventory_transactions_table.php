<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('inventory_item_id')
                ->constrained('inventory_items')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('inventory_user_id')
                ->nullable()
                ->constrained('inventory_users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('woms_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('inventory_request_type_id')
                ->nullable()
                ->constrained('inventory_request_types')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('transaction_type')->index();
            $table->decimal('quantity', 15, 3);
            $table->decimal('stock_before', 15, 3);
            $table->decimal('stock_after', 15, 3);
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference_number')->nullable()->index();
            $table->string('source')->default('system')->index();
            $table->string('item_uid_snapshot');
            $table->string('item_name_snapshot');
            $table->string('unit_snapshot', 30)->nullable();
            $table->dateTime('transaction_at')->index();
            $table->string('legacy_id')->nullable()->index();
            $table->json('legacy_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
