<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lhpp_bast_signatures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lhpp_bast_id')->constrained('lhpp_basts')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->string('role_key', 80)->index();
            $table->string('role_label');
            $table->foreignId('signer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signer_name_snapshot')->nullable();
            $table->string('signer_position_snapshot')->nullable();
            $table->string('signer_department_snapshot')->nullable();
            $table->string('signer_unit_snapshot')->nullable();
            $table->string('signer_section_snapshot')->nullable();
            $table->text('token')->nullable();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('status', 20)->default('locked')->index();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->longText('signature_data')->nullable();
            $table->string('signed_document_path')->nullable();
            $table->string('signed_document_original_name')->nullable();
            $table->string('signed_document_mime_type')->nullable();
            $table->timestamp('signed_document_uploaded_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->string('signed_ip', 45)->nullable();
            $table->text('signed_user_agent')->nullable();
            $table->timestamps();

            $table->unique(['lhpp_bast_id', 'step_order'], 'lhpp_bast_signatures_step_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lhpp_bast_signatures');
    }
};
