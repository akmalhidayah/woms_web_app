<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_handovers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('document_no', 80)->unique();
            $table->string('path', 20);
            $table->string('status', 40)->index();
            $table->dateTime('handed_over_at');

            $table->string('order_no_snapshot', 120);
            $table->text('job_name_snapshot');
            $table->string('unit_snapshot', 160)->nullable();
            $table->string('section_snapshot', 160)->nullable();

            $table->foreignId('admin_user_id')->constrained('users')->restrictOnDelete();
            $table->string('admin_name_snapshot', 160);
            $table->string('admin_position_snapshot', 160);
            $table->string('admin_signature_path', 255);
            $table->dateTime('admin_signed_at');
            $table->string('admin_signed_ip', 45)->nullable();
            $table->text('admin_signed_user_agent')->nullable();

            $table->foreignId('recipient_user_id')->constrained('users')->restrictOnDelete();
            $table->string('recipient_name_snapshot', 160);
            $table->string('recipient_position_snapshot', 160);
            $table->string('user_signature_path', 255)->nullable();
            $table->dateTime('user_signed_at')->nullable();
            $table->string('user_signed_ip', 45)->nullable();
            $table->text('user_signed_user_agent')->nullable();

            $table->json('photo_paths');
            $table->string('token_hash', 64)->nullable()->index();
            $table->text('token_encrypted')->nullable();
            $table->dateTime('token_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_handovers');
    }
};
