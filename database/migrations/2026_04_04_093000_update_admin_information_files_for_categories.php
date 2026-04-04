<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_information_files', function (Blueprint $table) {
            $table->dropUnique('admin_information_files_type_unique');
            $table->string('role')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('admin_information_files', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->unique('type');
        });
    }
};
