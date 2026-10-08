<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transaction_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transaction_id');
            $table->foreign(
                'inventory_transaction_id',
                'inv_tx_attach_transaction_fk'
            )
                ->references('id')
                ->on('inventory_transactions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('attachment_type')->index();
            $table->string('disk')->default('private');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transaction_attachments');
    }
};
