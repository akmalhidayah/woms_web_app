<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_work_type_sections')) {
            Schema::create('vendor_work_type_sections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_work_type_id')->constrained('vendor_work_types')->cascadeOnDelete();
                $table->string('name');
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('vendor_work_types', 'unit_work_section_id')) {
            $existingRows = DB::table('vendor_work_types')
                ->leftJoin('unit_work_sections', 'vendor_work_types.unit_work_section_id', '=', 'unit_work_sections.id')
                ->select([
                    'vendor_work_types.id',
                    'vendor_work_types.manager_id',
                    'unit_work_sections.name as section_name',
                ])
                ->get();

            foreach ($existingRows as $row) {
                if (DB::table('vendor_work_type_sections')->where('vendor_work_type_id', $row->id)->exists()) {
                    continue;
                }

                DB::table('vendor_work_type_sections')->insert([
                    'vendor_work_type_id' => $row->id,
                    'name' => $row->section_name ?: 'Seksi Vendor',
                    'manager_id' => $row->manager_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_work_type_sections');
    }
};
