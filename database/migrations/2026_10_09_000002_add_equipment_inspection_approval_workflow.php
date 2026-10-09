<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table): void {
            $table->text('workflow_error')->nullable();
            $table->text('revision_note')->nullable();
            $table->string('returned_by_name')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('final_pdf_path')->nullable();
            $table->char('final_pdf_sha256', 64)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->text('archive_error')->nullable();
        });
        Schema::create('equipment_inspection_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipment_inspection_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('document_version');
            $table->unsignedTinyInteger('step_order');
            $table->string('role_key', 32);
            $table->foreignId('signer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signer_name');
            $table->string('signer_position');
            $table->string('status', 24)->default('locked');
            $table->char('token_hash', 64)->nullable()->unique();
            $table->text('token_encrypted')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->string('email_status', 24)->default('not_sent');
            $table->uuid('email_attempt_id')->nullable();
            $table->timestamp('email_attempted_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();
            $table->unique(['equipment_inspection_id', 'document_version', 'step_order'], 'equipment_approval_step_unique');
            $table->unique(['equipment_inspection_id', 'document_version', 'role_key'], 'equipment_approval_role_unique');
            $table->index(['signer_user_id', 'status'], 'equipment_approval_inbox_index');
        });
        Schema::create('equipment_inspection_admin_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('equipment_inspection_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('document_version');
            $table->timestamp('viewed_at');
            $table->unique(['admin_user_id', 'equipment_inspection_id', 'document_version'], 'equipment_admin_read_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_inspection_admin_reads');
        Schema::dropIfExists('equipment_inspection_approvals');
        Schema::table('equipment_inspections', function (Blueprint $table): void {
            $table->dropColumn(['workflow_error', 'revision_note', 'returned_by_name', 'returned_at', 'final_pdf_path', 'final_pdf_sha256', 'archived_at', 'archive_error']);
        });
    }
};
