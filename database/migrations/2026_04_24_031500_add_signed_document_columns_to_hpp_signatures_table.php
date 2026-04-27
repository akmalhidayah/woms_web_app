<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hpp_signatures', function (Blueprint $table): void {
            $table->string('signed_document_path')->nullable()->after('signature_data');
            $table->string('signed_document_original_name')->nullable()->after('signed_document_path');
            $table->string('signed_document_mime_type')->nullable()->after('signed_document_original_name');
            $table->timestamp('signed_document_uploaded_at')->nullable()->after('signed_document_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('hpp_signatures', function (Blueprint $table): void {
            $table->dropColumn([
                'signed_document_path',
                'signed_document_original_name',
                'signed_document_mime_type',
                'signed_document_uploaded_at',
            ]);
        });
    }
};
