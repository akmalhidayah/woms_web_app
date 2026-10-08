<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'outline_agreement_monthly_realizations';

    private const CATEGORY_UNCATEGORIZED = 'belum_dikategorikan';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->string('kategori_biaya', 30)
                ->default(self::CATEGORY_UNCATEGORIZED);
            $table->unsignedBigInteger('amount')->default(0);
        });

        DB::table(self::TABLE)->update([
            'kategori_biaya' => self::CATEGORY_UNCATEGORIZED,
            'amount' => DB::raw('COALESCE(pr_po_amount, 0) + COALESCE(urgent_amount, 0)'),
        ]);

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropUnique('oa_monthly_realizations_period_unique');
            $table->unique(
                ['outline_agreement_id', 'year', 'month', 'kategori_biaya'],
                'oa_monthly_realizations_period_category_unique',
            );
            $table->index('kategori_biaya', 'oa_monthly_realizations_category_index');
        });

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropColumn(['pr_po_amount', 'urgent_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->unsignedBigInteger('pr_po_amount')->default(0);
            $table->unsignedBigInteger('urgent_amount')->default(0);
        });

        DB::table(self::TABLE)
            ->select('outline_agreement_id')
            ->distinct()
            ->orderBy('outline_agreement_id')
            ->chunk(100, function ($outlineAgreements): void {
                foreach ($outlineAgreements as $outlineAgreement) {
                    $groups = DB::table(self::TABLE)
                        ->where('outline_agreement_id', $outlineAgreement->outline_agreement_id)
                        ->selectRaw('MIN(id) as keeper_id, year, month, SUM(amount) as total_amount')
                        ->groupBy('year', 'month')
                        ->get();

                    foreach ($groups as $group) {
                        DB::table(self::TABLE)
                            ->where('id', $group->keeper_id)
                            ->update([
                                'pr_po_amount' => (int) $group->total_amount,
                                'urgent_amount' => 0,
                            ]);

                        DB::table(self::TABLE)
                            ->where('outline_agreement_id', $outlineAgreement->outline_agreement_id)
                            ->where('year', $group->year)
                            ->where('month', $group->month)
                            ->where('id', '<>', $group->keeper_id)
                            ->delete();
                    }
                }
            });

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropUnique('oa_monthly_realizations_period_category_unique');
            $table->dropIndex('oa_monthly_realizations_category_index');
            $table->dropColumn(['kategori_biaya', 'amount']);
        });

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->unique(
                ['outline_agreement_id', 'year', 'month'],
                'oa_monthly_realizations_period_unique',
            );
        });
    }
};
