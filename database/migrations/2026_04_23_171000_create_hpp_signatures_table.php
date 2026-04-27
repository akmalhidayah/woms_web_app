<?php

use App\Models\HppSignature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hpp_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hpp_id')->constrained('hpps')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->string('role_key', 80);
            $table->string('role_label', 120);
            $table->foreignId('signer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('signer_name_snapshot');
            $table->string('signer_position_snapshot');
            $table->string('signer_department_snapshot')->nullable();
            $table->string('signer_unit_snapshot')->nullable();
            $table->string('signer_section_snapshot')->nullable();
            $table->text('token')->nullable();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->enum('status', [
                HppSignature::STATUS_LOCKED,
                HppSignature::STATUS_PENDING,
                HppSignature::STATUS_SIGNED,
                HppSignature::STATUS_SKIPPED,
            ])->default(HppSignature::STATUS_LOCKED);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->longText('signature_data')->nullable();
            $table->string('signed_ip', 45)->nullable();
            $table->text('signed_user_agent')->nullable();
            $table->timestamps();

            $table->unique(['hpp_id', 'step_order']);
            $table->index(['hpp_id', 'status']);
            $table->index(['hpp_id', 'role_key']);
            $table->index(['signer_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hpp_signatures');
    }
};
