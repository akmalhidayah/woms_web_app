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
        Schema::table('hpps', function (Blueprint $table) {
            $table->string('document_no')->nullable()->unique()->after('id');
            $table->unsignedInteger('document_sequence')->nullable()->after('document_no');
            $table->unsignedSmallInteger('document_year')->nullable()->after('document_sequence');

            $table->unique(['document_year', 'document_sequence'], 'hpps_document_year_sequence_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hpps', function (Blueprint $table) {
            $table->dropUnique('hpps_document_year_sequence_unique');
            $table->dropUnique(['document_no']);
            $table->dropColumn([
                'document_year',
                'document_sequence',
                'document_no',
            ]);
        });
    }
};
