<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('approval_signature_rollbacks')) {
            return;
        }

        Schema::create('approval_signature_rollbacks', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type', 40)->index();
            $table->unsignedBigInteger('document_id')->index();
            $table->string('signature_type', 80)->index();
            $table->unsignedBigInteger('signature_id')->index();
            $table->unsignedSmallInteger('step_order');
            $table->string('role_key', 80)->nullable();
            $table->string('role_label', 120)->nullable();
            $table->unsignedBigInteger('signer_user_id')->nullable()->index();
            $table->string('signer_name')->nullable();
            $table->unsignedBigInteger('rollback_by')->nullable()->index();
            $table->text('rollback_reason');
            $table->timestamp('rolled_back_at')->index();
            $table->json('affected_signature_ids')->nullable();
            $table->json('previous_payload')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_signature_rollbacks');
    }
};
