<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lhpp_basts', function (Blueprint $table): void {
            $table->string('attachment_pdf_path')->nullable()->after('approval_flow');
            $table->string('attachment_pdf_original_name')->nullable()->after('attachment_pdf_path');
            $table->string('attachment_pdf_mime_type', 100)->nullable()->after('attachment_pdf_original_name');
            $table->unsignedBigInteger('attachment_pdf_size')->nullable()->after('attachment_pdf_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('lhpp_basts', function (Blueprint $table): void {
            $table->dropColumn([
                'attachment_pdf_path',
                'attachment_pdf_original_name',
                'attachment_pdf_mime_type',
                'attachment_pdf_size',
            ]);
        });
    }
};
