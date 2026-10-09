<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_inspections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('form_slug');
            $table->string('form_name');
            $table->json('template_snapshot');
            $table->char('template_hash', 64);
            $table->foreignId('inspector_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspector_name');
            $table->date('inspection_date');
            $table->string('document_no')->nullable()->unique();
            $table->unsignedInteger('document_sequence')->nullable();
            $table->unsignedSmallInteger('document_year')->nullable();
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('document_version')->default(1);
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->unique(['document_year', 'document_sequence'], 'equipment_document_sequence_unique');
            $table->index(['inspector_user_id', 'inspection_date', 'id'], 'equipment_inspector_history_index');
            $table->index(['inspector_user_id', 'status'], 'equipment_inspector_status_index');
        });

        Schema::create('equipment_inspection_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipment_inspection_id')->constrained()->restrictOnDelete();
            $table->string('item_key');
            $table->unsignedSmallInteger('position');
            $table->string('item_label');
            $table->string('group_key');
            $table->string('group_label')->nullable();
            $table->char('rating', 1)->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->unique(['equipment_inspection_id', 'item_key'], 'equipment_answer_item_unique');
        });

        Schema::create('equipment_inspection_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipment_inspection_id');
            $table->foreign('equipment_inspection_id', 'equipment_attachment_inspection_fk')->references('id')->on('equipment_inspections')->restrictOnDelete();
            $table->foreignId('equipment_inspection_answer_id');
            $table->foreign('equipment_inspection_answer_id', 'equipment_attachment_answer_fk')->references('id')->on('equipment_inspection_answers')->restrictOnDelete();
            $table->string('path');
            $table->string('mime_type', 64);
            $table->unsignedInteger('size');
            $table->char('sha256', 64);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('equipment_inspection_signatures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipment_inspection_id')->constrained()->restrictOnDelete();
            $table->string('role_key', 32);
            $table->unsignedTinyInteger('step');
            $table->foreignId('signer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signer_name');
            $table->string('signer_position');
            $table->string('signature_path');
            $table->char('signature_sha256', 64);
            $table->timestamp('signed_at');
            $table->unsignedInteger('document_version');
            $table->char('content_hash', 64);
            $table->json('signed_payload');
            $table->timestamps();
            $table->unique(['equipment_inspection_id', 'document_version', 'role_key'], 'equipment_signature_version_unique');
            $table->unique(['equipment_inspection_id', 'document_version', 'step'], 'equipment_signature_step_unique');
        });

        Schema::create('equipment_inspection_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipment_inspection_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 64);
            $table->unsignedInteger('document_version');
            $table->unsignedInteger('lock_version');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('equipment_inspection_counters', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_sequence')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_inspection_counters');
        Schema::dropIfExists('equipment_inspection_logs');
        Schema::dropIfExists('equipment_inspection_signatures');
        Schema::dropIfExists('equipment_inspection_attachments');
        Schema::dropIfExists('equipment_inspection_answers');
        Schema::dropIfExists('equipment_inspections');
    }
};
