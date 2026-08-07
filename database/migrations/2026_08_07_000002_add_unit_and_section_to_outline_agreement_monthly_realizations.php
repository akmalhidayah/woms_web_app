<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'outline_agreement_monthly_realizations';

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->string('unit_kerja')->nullable()->after('kategori_biaya');
            $table->string('seksi')->nullable()->after('unit_kerja');
        });

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropUnique('oa_monthly_realizations_period_category_unique');
            $table->unique(
                ['outline_agreement_id', 'year', 'month', 'kategori_biaya', 'unit_kerja', 'seksi'],
                'oa_month_real_cat_unit_sec_unique',
            );
            $table->index(
                ['year', 'month', 'kategori_biaya', 'seksi'],
                'oa_month_real_top_ten_index',
            );
        });
    }

    public function down(): void
    {
        DB::table(self::TABLE)
            ->select('outline_agreement_id')
            ->distinct()
            ->orderBy('outline_agreement_id')
            ->chunk(100, function ($outlineAgreements): void {
                foreach ($outlineAgreements as $outlineAgreement) {
                    $groups = DB::table(self::TABLE)
                        ->where('outline_agreement_id', $outlineAgreement->outline_agreement_id)
                        ->selectRaw('MIN(id) as keeper_id, year, month, kategori_biaya, SUM(amount) as total_amount')
                        ->groupBy('year', 'month', 'kategori_biaya')
                        ->get();

                    foreach ($groups as $group) {
                        DB::table(self::TABLE)
                            ->where('id', $group->keeper_id)
                            ->update(['amount' => (int) $group->total_amount]);

                        DB::table(self::TABLE)
                            ->where('outline_agreement_id', $outlineAgreement->outline_agreement_id)
                            ->where('year', $group->year)
                            ->where('month', $group->month)
                            ->where('kategori_biaya', $group->kategori_biaya)
                            ->where('id', '<>', $group->keeper_id)
                            ->delete();
                    }
                }
            });

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropUnique('oa_month_real_cat_unit_sec_unique');
            $table->dropIndex('oa_month_real_top_ten_index');
            $table->dropColumn(['unit_kerja', 'seksi']);
        });

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->unique(
                ['outline_agreement_id', 'year', 'month', 'kategori_biaya'],
                'oa_monthly_realizations_period_category_unique',
            );
        });
    }
};
