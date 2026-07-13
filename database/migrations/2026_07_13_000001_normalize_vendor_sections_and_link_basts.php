<?php

use App\Models\VendorWorkType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_work_type_sections', function (Blueprint $table): void {
            $table->string('normalized_name')->nullable()->after('name');
        });

        DB::table('vendor_work_type_sections')->orderBy('id')->get()->each(function (object $section): void {
            DB::table('vendor_work_type_sections')->where('id', $section->id)->update([
                'normalized_name' => Str::lower(trim((string) $section->name)),
            ]);
        });

        DB::table('vendor_work_type_sections')
            ->select('vendor_work_type_id', 'normalized_name')
            ->whereNotNull('normalized_name')
            ->groupBy('vendor_work_type_id', 'normalized_name')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $duplicate): void {
                $rows = DB::table('vendor_work_type_sections')
                    ->where('vendor_work_type_id', $duplicate->vendor_work_type_id)
                    ->where('normalized_name', $duplicate->normalized_name)
                    ->orderBy('id')
                    ->get();
                $primary = $rows->first();
                $managerId = $primary->manager_id ?: $rows->pluck('manager_id')->first(fn ($id) => $id !== null);

                DB::table('vendor_work_type_sections')->where('id', $primary->id)->update(['manager_id' => $managerId]);
                $removedIds = $rows->skip(1)->pluck('id')->all();
                DB::table('vendor_work_type_sections')->whereIn('id', $removedIds)->delete();

                Log::warning('Consolidated duplicate vendor sections.', [
                    'vendor_work_type_id' => $duplicate->vendor_work_type_id,
                    'normalized_name' => $duplicate->normalized_name,
                    'kept_id' => $primary->id,
                    'removed_ids' => $removedIds,
                ]);
            });

        Schema::table('vendor_work_type_sections', function (Blueprint $table): void {
            $table->unique(['vendor_work_type_id', 'normalized_name'], 'vendor_sections_vendor_normalized_unique');
        });

        Schema::table('lhpp_basts', function (Blueprint $table): void {
            $table->foreignId('vendor_work_type_section_id')->nullable()->after('tipe_pekerjaan')
                ->constrained('vendor_work_type_sections')->nullOnDelete();
        });

        $vendorId = DB::table('vendor_work_types')->where('name', VendorWorkType::FIXED_VENDOR_NAME)->value('id');
        if ($vendorId) {
            DB::table('lhpp_basts')->whereNull('vendor_work_type_section_id')->orderBy('id')->get()
                ->each(function (object $bast) use ($vendorId): void {
                    $normalized = Str::lower(trim((string) $bast->tipe_pekerjaan));
                    $sectionId = DB::table('vendor_work_type_sections')
                        ->where('vendor_work_type_id', $vendorId)
                        ->where('normalized_name', $normalized)
                        ->value('id');
                    if ($sectionId) {
                        DB::table('lhpp_basts')->where('id', $bast->id)->update(['vendor_work_type_section_id' => $sectionId]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('lhpp_basts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vendor_work_type_section_id');
        });
        Schema::table('vendor_work_type_sections', function (Blueprint $table): void {
            $table->dropUnique('vendor_sections_vendor_normalized_unique');
            $table->dropColumn('normalized_name');
        });
    }
};
