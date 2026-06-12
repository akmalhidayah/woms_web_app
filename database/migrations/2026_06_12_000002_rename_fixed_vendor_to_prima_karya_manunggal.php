<?php

use App\Models\VendorWorkType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_work_types')) {
            return;
        }

        DB::table('vendor_work_types')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['pt. pkm'])
            ->update([
                'name' => VendorWorkType::FIXED_VENDOR_NAME,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendor_work_types')) {
            return;
        }

        DB::table('vendor_work_types')
            ->where('name', VendorWorkType::FIXED_VENDOR_NAME)
            ->update([
                'name' => 'PT. PKM',
                'updated_at' => now(),
            ]);
    }
};
