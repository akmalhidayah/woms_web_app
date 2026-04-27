<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bengkel_display_settings', function (Blueprint $table): void {
            $table->id();
            $table->text('ticker_text')->nullable();
            $table->unsignedTinyInteger('ticker_speed_seconds')->default(18);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bengkel_display_settings');
    }
};
