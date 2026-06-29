<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notification_reads', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('notification_key', 191);
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'notification_key']);
            $table->index('notification_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_reads');
    }
};
