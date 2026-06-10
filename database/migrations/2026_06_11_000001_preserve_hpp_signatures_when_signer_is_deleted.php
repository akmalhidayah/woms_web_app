<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hpp_signatures', function (Blueprint $table): void {
            $table->dropForeign(['signer_user_id']);
        });

        Schema::table('hpp_signatures', function (Blueprint $table): void {
            $table->unsignedBigInteger('signer_user_id')->nullable()->change();
            $table->foreign('signer_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hpp_signatures', function (Blueprint $table): void {
            $table->dropForeign(['signer_user_id']);
            $table->foreign('signer_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
