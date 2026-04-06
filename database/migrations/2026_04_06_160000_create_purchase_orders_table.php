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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hpp_id')->unique()->constrained('hpps')->cascadeOnDelete();
            $table->string('purchase_order_number')->nullable()->index();
            $table->date('target_penyelesaian')->nullable()->index();
            $table->string('approval_target', 20)->nullable()->index();
            $table->text('approval_note')->nullable();
            $table->boolean('approve_manager')->default(false);
            $table->boolean('approve_senior_manager')->default(false);
            $table->boolean('approve_general_manager')->default(false);
            $table->boolean('approve_direktur_operasional')->default(false);
            $table->unsignedTinyInteger('progress_pekerjaan')->default(0);
            $table->string('po_document_path')->nullable();
            $table->text('vendor_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
