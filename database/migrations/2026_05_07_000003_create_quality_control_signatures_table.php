<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_control_signatures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quality_control_report_id')
                ->constrained('quality_control_reports')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->string('role_key', 50);
            $table->string('role_label');
            $table->foreignId('signer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signer_name')->nullable();
            $table->string('signer_position')->nullable();
            $table->string('source_department')->nullable();
            $table->string('source_unit')->nullable();
            $table->string('source_section')->nullable();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->text('token_encrypted')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('status', 24)->default('locked')->index();
            $table->longText('signature_data')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_ip')->nullable();
            $table->text('signed_user_agent')->nullable();
            $table->timestamps();

            $table->unique(['quality_control_report_id', 'role_key'], 'qc_signatures_report_role_unique');
            $table->index(['quality_control_report_id', 'step_order'], 'qc_signatures_report_step_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_control_signatures');
    }
};
