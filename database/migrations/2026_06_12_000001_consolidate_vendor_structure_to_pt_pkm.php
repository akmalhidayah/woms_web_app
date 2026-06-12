<?php

use App\Models\VendorWorkType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_work_types') || ! Schema::hasTable('vendor_work_type_sections')) {
            return;
        }

        $canonicalId = DB::table('vendor_work_types')
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(VendorWorkType::FIXED_VENDOR_NAME)])
            ->value('id');

        if (! $canonicalId) {
            $canonicalId = DB::table('vendor_work_types')->insertGetId([
                'name' => VendorWorkType::FIXED_VENDOR_NAME,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('vendor_work_types')
                ->where('id', $canonicalId)
                ->update([
                    'name' => VendorWorkType::FIXED_VENDOR_NAME,
                    'updated_at' => now(),
                ]);
        }

        $sections = DB::table('vendor_work_type_sections')
            ->orderByRaw('CASE WHEN vendor_work_type_id = ? THEN 0 ELSE 1 END', [$canonicalId])
            ->orderBy('id')
            ->get();
        $sectionByName = [];

        foreach ($sections as $section) {
            $normalizedName = strtolower(trim((string) $section->name));

            if (isset($sectionByName[$normalizedName])) {
                $existing = $sectionByName[$normalizedName];

                if (! $existing->manager_id && $section->manager_id) {
                    DB::table('vendor_work_type_sections')
                        ->where('id', $existing->id)
                        ->update([
                            'manager_id' => $section->manager_id,
                            'updated_at' => now(),
                        ]);
                    $existing->manager_id = $section->manager_id;
                }

                DB::table('vendor_work_type_sections')->where('id', $section->id)->delete();

                continue;
            }

            DB::table('vendor_work_type_sections')
                ->where('id', $section->id)
                ->update([
                    'vendor_work_type_id' => $canonicalId,
                    'name' => trim((string) $section->name),
                    'updated_at' => now(),
                ]);

            $section->vendor_work_type_id = $canonicalId;
            $section->name = trim((string) $section->name);
            $sectionByName[$normalizedName] = $section;
        }

        DB::table('vendor_work_types')
            ->where('id', '!=', $canonicalId)
            ->delete();
    }

    public function down(): void
    {
        // Consolidated vendor data cannot be split back into its previous groups safely.
    }
};
