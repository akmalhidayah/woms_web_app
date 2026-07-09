<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lhpp_basts', function (Blueprint $table) {
            $table->string('document_no')->nullable()->after('id');
            $table->unsignedInteger('document_sequence')->nullable()->after('document_no');
            $table->unsignedSmallInteger('document_year')->nullable()->after('document_sequence');

            $table->unique(['document_year', 'document_sequence', 'termin_type'], 'lhpp_basts_document_sequence_termin_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lhpp_basts', function (Blueprint $table) {
            $table->dropUnique('lhpp_basts_document_sequence_termin_unique');
            $table->dropColumn([
                'document_year',
                'document_sequence',
                'document_no',
            ]);
        });
    }
};
