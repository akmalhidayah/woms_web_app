<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LpjPpl;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementMonthlyRealization;
use App\Models\OutlineAgreementTarget;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final class DashboardFinancialSummaryService
{
    /**
     * @return array{
     *     contract_budget: int,
     *     system_realization: int,
     *     manual_realization: int,
     *     realization: int,
     *     outstanding: int,
     *     prognosis: int,
     *     available_budget: int,
     *     prognosis_percentage_hundredths: int,
     *     realization_percentage_hundredths: int,
     *     outstanding_percentage_hundredths: int,
     *     available_budget_percentage_hundredths: int,
     *     lpj_ppl_in_process: int
     * }
     */
    public function resolve(?string $costCategory = null): array
    {
        $this->assertValidCostCategory($costCategory);

        $contractBudget = $this->moneyInt(OutlineAgreement::query()
            ->where('status', OutlineAgreement::STATUS_ACTIVE)
            ->sum('current_total_nilai'));

        $manualRealizationQuery = OutlineAgreementMonthlyRealization::query()
            ->whereHas('outlineAgreement', fn (Builder $query): Builder => $query
                ->where('status', OutlineAgreement::STATUS_ACTIVE));

        if ($costCategory !== null) {
            $manualRealizationQuery->where('kategori_biaya', $costCategory);
        }

        $manualRealization = $this->moneyInt($manualRealizationQuery->sum('amount'));

        [$systemRealization, $realizationByHpp, $legacyRealizationByOrder, $processByHpp] =
            $this->resolveSystemRealizations($costCategory);

        $lpjPplInProcess = 0;
        $outstanding = $this->latestActiveHpps($costCategory)
            ->sum(function (Hpp $hpp) use (
                $realizationByHpp,
                $legacyRealizationByOrder,
                $processByHpp,
                &$lpjPplInProcess,
            ): int {
                $realized = ($realizationByHpp[$hpp->id] ?? 0)
                    + ($legacyRealizationByOrder[$hpp->order_id] ?? 0);
                $hppOutstanding = max($this->moneyInt($hpp->total_keseluruhan) - $realized, 0);
                $lpjPplInProcess += min($processByHpp[$hpp->id] ?? 0, $hppOutstanding);

                return $hppOutstanding;
            });

        $realization = $systemRealization + $manualRealization;
        $prognosis = $realization + $outstanding;
        $availableBudget = $contractBudget - $prognosis;

        return [
            'contract_budget' => $contractBudget,
            'system_realization' => $systemRealization,
            'manual_realization' => $manualRealization,
            'realization' => $realization,
            'outstanding' => $outstanding,
            'prognosis' => $prognosis,
            'available_budget' => $availableBudget,
            'prognosis_percentage_hundredths' => $this->percentageHundredths($prognosis, $contractBudget),
            'realization_percentage_hundredths' => $this->percentageHundredths($realization, $contractBudget),
            'outstanding_percentage_hundredths' => $this->percentageHundredths($outstanding, $contractBudget),
            'available_budget_percentage_hundredths' => $this->percentageHundredths($availableBudget, $contractBudget),
            'lpj_ppl_in_process' => $lpjPplInProcess,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function resolveForCategory(string $costCategory): array
    {
        return $this->resolve($costCategory);
    }

    /**
     * @return array<string, int>
     */
    public function resolveMaintenanceSummary(int $year): array
    {
        $summary = $this->resolveForCategory('pemeliharaan');
        $annualTarget = $this->moneyInt(OutlineAgreementTarget::query()
            ->where('tahun', $year)
            ->whereHas('outlineAgreement', fn (Builder $query): Builder => $query
                ->where('status', OutlineAgreement::STATUS_ACTIVE))
            ->sum('nilai_target'));

        return $summary + [
            'target_year' => $year,
            'annual_target' => $annualTarget,
            'remaining_target' => $annualTarget - $summary['prognosis'],
            'target_usage_percentage_hundredths' => $this->percentageHundredths(
                $summary['prognosis'],
                $annualTarget,
            ),
            'already_realized' => $summary['realization'],
        ];
    }

    /**
     * @return array{0: int, 1: array<int, int>, 2: array<int, int>, 3: array<int, int>}
     */
    private function resolveSystemRealizations(?string $costCategory): array
    {
        $realizationByHpp = [];
        $legacyRealizationByOrder = [];
        $processByHpp = [];
        $total = 0;

        $rows = LhppBast::query()
            ->where('termin_type', 'termin_1')
            ->whereNull('parent_lhpp_bast_id')
            ->when($costCategory !== null, fn (Builder $query): Builder => $query
                ->whereHas('hpp.budgetVerification', fn (Builder $verificationQuery): Builder => $verificationQuery
                    ->where('kategori_biaya', $costCategory)))
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('hpp.outlineAgreement', fn (Builder $agreementQuery): Builder => $agreementQuery
                        ->where('status', OutlineAgreement::STATUS_ACTIVE))
                    ->orWhere(function (Builder $legacyQuery): void {
                        $legacyQuery
                            ->whereNull('hpp_id')
                            ->whereHas('order.latestHpp.outlineAgreement', fn (Builder $agreementQuery): Builder => $agreementQuery
                                ->where('status', OutlineAgreement::STATUS_ACTIVE));
                    });
            })
            ->with([
                'garansi:id,lhpp_bast_id,garansi_months',
                'lpjPpl:id,lhpp_bast_id,lpj_number_termin1,ppl_number_termin1,lpj_document_path_termin1,ppl_document_path_termin1,lpj_number_termin2,ppl_number_termin2,lpj_document_path_termin2,ppl_document_path_termin2',
                'terminTwo:id,parent_lhpp_bast_id,approval_status',
            ])
            ->get([
                'id',
                'order_id',
                'hpp_id',
                'total_aktual_biaya',
                'termin_1_nilai',
                'termin_2_nilai',
                'termin1_status',
                'termin2_status',
                'approval_status',
            ]);

        foreach ($rows as $bast) {
            $realized = $this->realizedAmount($bast);
            $inProcess = $this->lpjPplInProcessAmount($bast);
            $total += $realized;

            if ($bast->hpp_id !== null) {
                $realizationByHpp[$bast->hpp_id] = ($realizationByHpp[$bast->hpp_id] ?? 0) + $realized;
                $processByHpp[$bast->hpp_id] = ($processByHpp[$bast->hpp_id] ?? 0) + $inProcess;
            } else {
                $legacyRealizationByOrder[$bast->order_id] =
                    ($legacyRealizationByOrder[$bast->order_id] ?? 0) + $realized;
            }
        }

        return [$total, $realizationByHpp, $legacyRealizationByOrder, $processByHpp];
    }

    private function lpjPplInProcessAmount(LhppBast $bast): int
    {
        if ($bast->approval_status !== LhppBast::APPROVAL_APPROVED) {
            return 0;
        }

        $lpjPpl = $bast->lpjPpl;
        $terminOneRealized = $lpjPpl !== null
            && $bast->termin1_status === 'sudah'
            && $this->hasCompletePackage($lpjPpl, 1);
        $garansiMonths = $bast->garansi?->garansi_months;

        if ($garansiMonths !== null && (int) $garansiMonths === 0) {
            return $terminOneRealized ? 0 : max($this->moneyInt($bast->total_aktual_biaya), 0);
        }

        if (! $terminOneRealized) {
            return max($this->moneyInt($bast->termin_1_nilai), 0);
        }

        $terminTwoRealized = $lpjPpl !== null
            && $bast->termin2_status === 'sudah'
            && $this->hasCompletePackage($lpjPpl, 2);

        if ($bast->terminTwo?->approval_status !== LhppBast::APPROVAL_APPROVED || $terminTwoRealized) {
            return 0;
        }

        return max($this->moneyInt($bast->termin_2_nilai), 0);
    }

    private function realizedAmount(LhppBast $bast): int
    {
        $lpjPpl = $bast->lpjPpl;

        if (! $lpjPpl) {
            return 0;
        }

        $actualAmount = max($this->moneyInt($bast->total_aktual_biaya), 0);
        $terminOnePaid = $bast->termin1_status === 'sudah' && $this->hasCompletePackage($lpjPpl, 1);
        $garansiMonths = $bast->garansi?->garansi_months;

        if ((int) $garansiMonths === 0 && $garansiMonths !== null) {
            return $terminOnePaid ? $actualAmount : 0;
        }

        $realized = $terminOnePaid ? max($this->moneyInt($bast->termin_1_nilai), 0) : 0;

        if ($bast->termin2_status === 'sudah' && $this->hasCompletePackage($lpjPpl, 2)) {
            $realized += max($this->moneyInt($bast->termin_2_nilai), 0);
        }

        return min($realized, $actualAmount);
    }

    private function hasCompletePackage(LpjPpl $lpjPpl, int $termin): bool
    {
        $suffix = "termin{$termin}";

        return $this->hasValue($lpjPpl->{"lpj_number_{$suffix}"})
            && $this->hasValue($lpjPpl->{"ppl_number_{$suffix}"})
            && $this->hasValue($lpjPpl->{"lpj_document_path_{$suffix}"})
            && $this->hasValue($lpjPpl->{"ppl_document_path_{$suffix}"});
    }

    private function hasValue(mixed $value): bool
    {
        return trim((string) $value) !== '';
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Hpp> */
    private function latestActiveHpps(?string $costCategory): \Illuminate\Database\Eloquent\Collection
    {
        return Hpp::query()
            ->whereHas('outlineAgreement', fn (Builder $query): Builder => $query
                ->where('status', OutlineAgreement::STATUS_ACTIVE))
            ->whereIn('status', [
                Hpp::STATUS_DRAFT,
                Hpp::STATUS_IN_REVIEW,
                Hpp::STATUS_APPROVED,
            ])
            ->when($costCategory !== null, fn (Builder $query): Builder => $query
                ->whereHas('budgetVerification', fn (Builder $verificationQuery): Builder => $verificationQuery
                    ->where('kategori_biaya', $costCategory)))
            ->whereNotExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('hpps as newer_hpps')
                    ->whereColumn('newer_hpps.order_id', 'hpps.order_id')
                    ->whereColumn('newer_hpps.id', '>', 'hpps.id');
            })
            ->get(['id', 'order_id', 'total_keseluruhan']);
    }

    private function assertValidCostCategory(?string $costCategory): void
    {
        if ($costCategory !== null && ! array_key_exists($costCategory, BudgetVerification::kategoriBiayaOptions())) {
            throw new InvalidArgumentException("Kategori biaya {$costCategory} tidak valid.");
        }
    }

    private function percentageHundredths(int $amount, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        $scaledAmount = $amount * 10000;
        $percentage = intdiv($scaledAmount, $total);
        $remainder = $scaledAmount % $total;

        return ($remainder * 2) >= $total ? $percentage + 1 : $percentage;
    }

    private function moneyInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        $normalized = trim((string) $value);

        if (! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $normalized, $matches)) {
            return 0;
        }

        $absolute = (int) $matches[2];
        $fraction = $matches[3] ?? '';

        if ($fraction !== '' && (int) $fraction[0] >= 5) {
            $absolute++;
        }

        return ($matches[1] ?? '') === '-' ? -$absolute : $absolute;
    }
}
