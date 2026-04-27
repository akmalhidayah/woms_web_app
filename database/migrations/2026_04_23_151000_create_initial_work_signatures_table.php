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
        Schema::create('initial_work_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initial_work_id')->constrained('initial_works')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->string('role_key', 40);
            $table->string('role_label');
            $table->foreignId('signer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signer_name')->nullable();
            $table->string('signer_position')->nullable();
            $table->string('source_department')->nullable();
            $table->string('source_unit')->nullable();
            $table->string('source_section')->nullable();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('status', 24)->default('locked')->index();
            $table->string('signature_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_ip')->nullable();
            $table->text('signed_user_agent')->nullable();
            $table->timestamps();

            $table->unique(['initial_work_id', 'role_key']);
            $table->index(['initial_work_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initial_work_signatures');
    }
};
