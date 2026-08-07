<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LpjPpl;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementMonthlyRealization;
use Illuminate\Database\Eloquent\Builder;

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
     *     available_budget_percentage_hundredths: int
     * }
     */
    public function resolve(): array
    {
        $contractBudget = $this->moneyInt(OutlineAgreement::query()
            ->where('status', OutlineAgreement::STATUS_ACTIVE)
            ->sum('current_total_nilai'));

        $manualRealization = $this->moneyInt(OutlineAgreementMonthlyRealization::query()
            ->whereHas('outlineAgreement', fn (Builder $query): Builder => $query
                ->where('status', OutlineAgreement::STATUS_ACTIVE))
            ->sum('amount'));

        [$systemRealization, $realizationByHpp, $legacyRealizationByOrder] =
            $this->resolveSystemRealizations();

        $outstanding = $this->latestActiveHpps()
            ->sum(function (Hpp $hpp) use ($realizationByHpp, $legacyRealizationByOrder): int {
                $realized = ($realizationByHpp[$hpp->id] ?? 0)
                    + ($legacyRealizationByOrder[$hpp->order_id] ?? 0);

                return max($this->moneyInt($hpp->total_keseluruhan) - $realized, 0);
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
        ];
    }

    /**
     * @return array{0: int, 1: array<int, int>, 2: array<int, int>}
     */
    private function resolveSystemRealizations(): array
    {
        $realizationByHpp = [];
        $legacyRealizationByOrder = [];
        $total = 0;

        $rows = LhppBast::query()
            ->where('termin_type', 'termin_1')
            ->whereNull('parent_lhpp_bast_id')
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
            ]);

        foreach ($rows as $bast) {
            $realized = $this->realizedAmount($bast);
            $total += $realized;

            if ($bast->hpp_id !== null) {
                $realizationByHpp[$bast->hpp_id] = ($realizationByHpp[$bast->hpp_id] ?? 0) + $realized;
            } else {
                $legacyRealizationByOrder[$bast->order_id] =
                    ($legacyRealizationByOrder[$bast->order_id] ?? 0) + $realized;
            }
        }

        return [$total, $realizationByHpp, $legacyRealizationByOrder];
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
    private function latestActiveHpps(): \Illuminate\Database\Eloquent\Collection
    {
        return Hpp::query()
            ->whereHas('outlineAgreement', fn (Builder $query): Builder => $query
                ->where('status', OutlineAgreement::STATUS_ACTIVE))
            ->whereIn('status', [
                Hpp::STATUS_DRAFT,
                Hpp::STATUS_IN_REVIEW,
                Hpp::STATUS_APPROVED,
            ])
            ->whereNotExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('hpps as newer_hpps')
                    ->whereColumn('newer_hpps.order_id', 'hpps.order_id')
                    ->whereColumn('newer_hpps.id', '>', 'hpps.id');
            })
            ->get(['id', 'order_id', 'total_keseluruhan']);
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
