<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Some production installations have a legacy bengkel_pics.id type that
        // differs from Laravel's foreignId type. Keep this nullable relation at
        // application level so the migration also survives a previous partial run.
        if (! Schema::hasColumn('equipment_inspection_forms', 'bengkel_pic_id')) {
            Schema::table('equipment_inspection_forms', function (Blueprint $table): void {
                $table->unsignedBigInteger('bengkel_pic_id')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('equipment_inspection_forms', 'bengkel_pic_id')) {
            Schema::table('equipment_inspection_forms', function (Blueprint $table): void {
                $table->dropColumn('bengkel_pic_id');
            });
        }
    }
};
