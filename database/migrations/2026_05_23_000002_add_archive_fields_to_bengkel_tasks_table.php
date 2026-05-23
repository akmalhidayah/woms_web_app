<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bengkel_tasks', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('attachment_size')->index();
            $table->foreignId('archived_order_id')
                ->nullable()
                ->after('archived_at')
                ->constrained('orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bengkel_tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('archived_order_id');
            $table->dropColumn('archived_at');
        });
    }
};
