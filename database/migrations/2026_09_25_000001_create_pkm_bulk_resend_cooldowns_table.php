<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pkm_bulk_resend_cooldowns', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type', 20)->unique();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pkm_bulk_resend_cooldowns');
    }
};
