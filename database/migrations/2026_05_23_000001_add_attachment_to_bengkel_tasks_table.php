<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bengkel_tasks', function (Blueprint $table): void {
            $table->string('attachment_path')->nullable()->after('person_in_charge_profiles');
            $table->string('attachment_original_name')->nullable()->after('attachment_path');
            $table->string('attachment_mime_type', 100)->nullable()->after('attachment_original_name');
            $table->unsignedInteger('attachment_size')->nullable()->after('attachment_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('bengkel_tasks', function (Blueprint $table): void {
            $table->dropColumn([
                'attachment_path',
                'attachment_original_name',
                'attachment_mime_type',
                'attachment_size',
            ]);
        });
    }
};
