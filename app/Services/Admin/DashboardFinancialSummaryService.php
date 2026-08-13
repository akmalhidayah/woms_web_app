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
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
     *     lpj_status_amount: int,
     *     invoice_status_amount: int,
     *     outstanding_stages: array{hpp: int, purchase_order: int, lpj_process: int},
     *     classified_outstanding: int,
     *     unclassified_outstanding: int
     * }
     */
    public function resolve(
        ?string $costCategory = null,
        ?int $outlineAgreementId = null,
        ?int $year = null,
    ): array {
        $this->assertValidCostCategory($costCategory);

        $contractBudgetQuery = OutlineAgreement::query();
        $outlineAgreementId === null
            ? $contractBudgetQuery->where('status', OutlineAgreement::STATUS_ACTIVE)
            : $contractBudgetQuery->whereKey($outlineAgreementId);
        $contractBudget = $this->moneyInt($contractBudgetQuery->sum('current_total_nilai'));

        $manualRealizationQuery = OutlineAgreementMonthlyRealization::query()
            ->when(
                $outlineAgreementId !== null,
                fn (Builder $query): Builder => $query->where('outline_agreement_id', $outlineAgreementId),
                fn (Builder $query): Builder => $query->whereHas('outlineAgreement', fn (Builder $agreementQuery): Builder => $agreementQuery
                    ->where('status', OutlineAgreement::STATUS_ACTIVE)),
            )
            ->when($year !== null, fn (Builder $query): Builder => $query->where('year', $year));

        if ($costCategory !== null) {
            $manualRealizationQuery->where('kategori_biaya', $costCategory);
        }

        $manualRealization = $this->moneyInt($manualRealizationQuery->sum('amount'));

        [$systemRealization, $realizationByHpp, $legacyRealizationByOrder, $lpjStatusByHpp, $invoiceStatusByHpp] =
            $this->resolveSystemRealizations($costCategory, $outlineAgreementId, $year);

        $lpjStatusAmount = 0;
        $invoiceStatusAmount = 0;
        $outstandingStages = [
            'hpp' => 0,
            'purchase_order' => 0,
            'lpj_process' => 0,
        ];
        $outstanding = $this->latestActiveHpps($costCategory, $outlineAgreementId, $year)
            ->sum(function (Hpp $hpp) use (
                $realizationByHpp,
                $legacyRealizationByOrder,
                $lpjStatusByHpp,
                $invoiceStatusByHpp,
                &$lpjStatusAmount,
                &$invoiceStatusAmount,
                &$outstandingStages,
            ): int {
                $realized = ($realizationByHpp[$hpp->id] ?? 0)
                    + ($legacyRealizationByOrder[$hpp->order_id] ?? 0);
                $hppOutstanding = max($this->moneyInt($hpp->total_keseluruhan) - $realized, 0);
                $lpjStatusAmount += $lpjStatusByHpp[$hpp->id] ?? 0;
                $invoiceStatusAmount += $invoiceStatusByHpp[$hpp->id] ?? 0;

                $stage = $this->outstandingStage($hpp, $hppOutstanding);
                if ($stage !== null) {
                    $outstandingStages[$stage] += $hppOutstanding;
                }

                return $hppOutstanding;
            });

        $realization = $systemRealization + $manualRealization;
        $prognosis = $realization + $outstanding;
        $availableBudget = $contractBudget - $prognosis;
        $classifiedOutstanding = array_sum($outstandingStages);

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
            'lpj_status_amount' => $lpjStatusAmount,
            'invoice_status_amount' => $invoiceStatusAmount,
            'outstanding_stages' => $outstandingStages,
            'classified_outstanding' => $classifiedOutstanding,
            'unclassified_outstanding' => max($outstanding - $classifiedOutstanding, 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveForCategory(
        string $costCategory,
        ?int $outlineAgreementId = null,
        ?int $year = null,
    ): array {
        return $this->resolve($costCategory, $outlineAgreementId, $year);
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveMaintenanceSummary(?int $year, ?int $outlineAgreementId = null): array
    {
        $summary = $this->resolveForCategory('pemeliharaan', $outlineAgreementId, $year);
        $annualTarget = $this->moneyInt(OutlineAgreementTarget::query()
            ->when($year !== null, fn (Builder $query): Builder => $query->where('tahun', $year))
            ->when(
                $outlineAgreementId !== null,
                fn (Builder $query): Builder => $query->where('outline_agreement_id', $outlineAgreementId),
                fn (Builder $query): Builder => $query->whereHas('outlineAgreement', fn (Builder $agreementQuery): Builder => $agreementQuery
                    ->where('status', OutlineAgreement::STATUS_ACTIVE)),
            )
            ->sum('nilai_target'));

        return $summary + [
            'target_year' => $year ?? 'Semua Tahun',
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
     * Return valid system-realization events for the dashboard chart.
     *
     * `tanggal_bast` is only the monthly bucket. Eligibility and amount use
     * the same approval/LPJ rules as the financial summary cards.
     *
     * @return Collection<int, array{date: CarbonInterface, amount: int, category: string|null}>
     */
    public function realizationEvents(
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        ?int $outlineAgreementId = null,
    ): Collection {
        return $this->systemRealizationQuery(outlineAgreementId: $outlineAgreementId)
            ->whereBetween('tanggal_bast', [$startDate->toDateString(), $endDate->toDateString()])
            ->with([
                'hpp:id',
                'hpp.budgetVerification:id,hpp_id,kategori_biaya',
                'order:id',
                'order.latestHpp',
                'order.latestHpp.budgetVerification:id,hpp_id,kategori_biaya',
            ])
            ->orderBy('tanggal_bast')
            ->get([
                'id',
                'order_id',
                'hpp_id',
                'tanggal_bast',
                'total_aktual_biaya',
                'termin_1_nilai',
                'termin_2_nilai',
                'approval_status',
            ])
            ->map(fn (LhppBast $bast): array => [
                'date' => $bast->tanggal_bast,
                'amount' => $this->realizedAmount($bast),
                'category' => $bast->hpp?->budgetVerification?->kategori_biaya
                    ?? $bast->order?->latestHpp?->budgetVerification?->kategori_biaya,
            ])
            ->filter(fn (array $event): bool => $event['amount'] > 0 && $event['date'] !== null)
            ->values();
    }

    /**
     * @return array{0: int, 1: array<int, int>, 2: array<int, int>, 3: array<int, int>, 4: array<int, int>}
     */
    private function resolveSystemRealizations(
        ?string $costCategory,
        ?int $outlineAgreementId,
        ?int $year,
    ): array {
        $realizationByHpp = [];
        $legacyRealizationByOrder = [];
        $lpjStatusByHpp = [];
        $invoiceByHpp = [];
        $total = 0;

        $rows = $this->systemRealizationQuery($costCategory, $outlineAgreementId, $year)
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
            $lpjStatus = $this->lpjStatusAmount($bast);
            $invoiceStatus = $this->invoiceStatusAmount($bast);
            $total += $realized;

            if ($bast->hpp_id !== null) {
                $realizationByHpp[$bast->hpp_id] = ($realizationByHpp[$bast->hpp_id] ?? 0) + $realized;
                $lpjStatusByHpp[$bast->hpp_id] = ($lpjStatusByHpp[$bast->hpp_id] ?? 0) + $lpjStatus;
                $invoiceByHpp[$bast->hpp_id] = ($invoiceByHpp[$bast->hpp_id] ?? 0) + $invoiceStatus;
            } else {
                $legacyRealizationByOrder[$bast->order_id] =
                    ($legacyRealizationByOrder[$bast->order_id] ?? 0) + $realized;
            }
        }

        return [$total, $realizationByHpp, $legacyRealizationByOrder, $lpjStatusByHpp, $invoiceByHpp];
    }

    private function systemRealizationQuery(
        ?string $costCategory = null,
        ?int $outlineAgreementId = null,
        ?int $year = null,
    ): Builder {
        return LhppBast::query()
            ->where('termin_type', 'termin_1')
            ->whereNull('parent_lhpp_bast_id')
            ->when($costCategory !== null, function (Builder $query) use ($costCategory): void {
                $query->where(function (Builder $categoryQuery) use ($costCategory): void {
                    $categoryQuery
                        ->whereHas('hpp.budgetVerification', fn (Builder $verificationQuery): Builder => $verificationQuery
                            ->where('kategori_biaya', $costCategory))
                        ->orWhere(function (Builder $legacyQuery) use ($costCategory): void {
                            $legacyQuery
                                ->whereNull('hpp_id')
                                ->whereHas('order.latestHpp.budgetVerification', fn (Builder $verificationQuery): Builder => $verificationQuery
                                    ->where('kategori_biaya', $costCategory));
                        });
                });
            })
            ->where(function (Builder $query) use ($outlineAgreementId): void {
                $query->whereHas('hpp.outlineAgreement', function (Builder $agreementQuery) use ($outlineAgreementId): void {
                    $outlineAgreementId === null
                        ? $agreementQuery->where('status', OutlineAgreement::STATUS_ACTIVE)
                        : $agreementQuery->whereKey($outlineAgreementId);
                })->orWhere(function (Builder $legacyQuery) use ($outlineAgreementId): void {
                    $legacyQuery
                        ->whereNull('hpp_id')
                        ->whereHas('order.latestHpp.outlineAgreement', function (Builder $agreementQuery) use ($outlineAgreementId): void {
                            $outlineAgreementId === null
                                ? $agreementQuery->where('status', OutlineAgreement::STATUS_ACTIVE)
                                : $agreementQuery->whereKey($outlineAgreementId);
                        });
                });
            })
            ->when($year !== null, fn (Builder $query): Builder => $query
                ->whereBetween('tanggal_bast', ["{$year}-01-01", "{$year}-12-31"]))
            ->with([
                'garansi:id,lhpp_bast_id,garansi_months',
                'lpjPpl:id,lhpp_bast_id,lpj_number_termin1,ppl_number_termin1,lpj_document_path_termin1,ppl_document_path_termin1,lpj_number_termin2,ppl_number_termin2,lpj_document_path_termin2,ppl_document_path_termin2',
                'terminTwo:id,parent_lhpp_bast_id,approval_status',
            ]);
    }

    private function lpjStatusAmount(LhppBast $bast): int
    {
        if ($bast->approval_status !== LhppBast::APPROVAL_APPROVED) {
            return 0;
        }

        $lpjPpl = $bast->lpjPpl;
        if (! $lpjPpl) {
            return 0;
        }

        $terminOneLpjComplete = $this->hasCompleteLpj($lpjPpl, 1);
        $terminOneInvoiceComplete = $this->hasCompletePackage($lpjPpl, 1);
        $garansiMonths = $bast->garansi?->garansi_months;

        if ($garansiMonths !== null && (int) $garansiMonths === 0) {
            return $terminOneLpjComplete && ! $terminOneInvoiceComplete
                ? max($this->moneyInt($bast->total_aktual_biaya), 0)
                : 0;
        }

        $lpjStatusAmount = $terminOneLpjComplete && ! $terminOneInvoiceComplete
            ? max($this->moneyInt($bast->termin_1_nilai), 0)
            : 0;

        if ($bast->terminTwo?->approval_status !== LhppBast::APPROVAL_APPROVED) {
            return $lpjStatusAmount;
        }

        $terminTwoLpjComplete = $this->hasCompleteLpj($lpjPpl, 2);
        $terminTwoInvoiceComplete = $this->hasCompletePackage($lpjPpl, 2);

        if ($terminTwoLpjComplete && ! $terminTwoInvoiceComplete) {
            $lpjStatusAmount += max($this->moneyInt($bast->termin_2_nilai), 0);
        }

        return min($lpjStatusAmount, max($this->moneyInt($bast->total_aktual_biaya), 0));
    }

    private function invoiceStatusAmount(LhppBast $bast): int
    {
        if ($bast->approval_status !== LhppBast::APPROVAL_APPROVED) {
            return 0;
        }

        $lpjPpl = $bast->lpjPpl;

        if (! $lpjPpl) {
            return 0;
        }

        $actualAmount = max($this->moneyInt($bast->total_aktual_biaya), 0);
        $garansiMonths = $bast->garansi?->garansi_months;

        if ($garansiMonths !== null && (int) $garansiMonths === 0) {
            return $this->hasCompletePackage($lpjPpl, 1) ? $actualAmount : 0;
        }

        $invoiceAmount = $this->hasCompletePackage($lpjPpl, 1)
            ? max($this->moneyInt($bast->termin_1_nilai), 0)
            : 0;

        if ($bast->terminTwo?->approval_status === LhppBast::APPROVAL_APPROVED
            && $this->hasCompletePackage($lpjPpl, 2)) {
            $invoiceAmount += max($this->moneyInt($bast->termin_2_nilai), 0);
        }

        return min($invoiceAmount, $actualAmount);
    }

    private function realizedAmount(LhppBast $bast): int
    {
        if ($bast->approval_status !== LhppBast::APPROVAL_APPROVED) {
            return 0;
        }

        $lpjPpl = $bast->lpjPpl;

        if (! $lpjPpl) {
            return 0;
        }

        $actualAmount = max($this->moneyInt($bast->total_aktual_biaya), 0);
        $terminOneLpjComplete = $this->hasCompleteLpj($lpjPpl, 1);
        $garansiMonths = $bast->garansi?->garansi_months;

        if ((int) $garansiMonths === 0 && $garansiMonths !== null) {
            return $terminOneLpjComplete ? $actualAmount : 0;
        }

        $realized = $terminOneLpjComplete ? max($this->moneyInt($bast->termin_1_nilai), 0) : 0;

        if ($bast->terminTwo?->approval_status === LhppBast::APPROVAL_APPROVED
            && $this->hasCompleteLpj($lpjPpl, 2)) {
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

    private function hasCompleteLpj(LpjPpl $lpjPpl, int $termin): bool
    {
        $suffix = "termin{$termin}";

        return $this->hasValue($lpjPpl->{"lpj_number_{$suffix}"})
            && $this->hasValue($lpjPpl->{"lpj_document_path_{$suffix}"});
    }

    private function hasValue(mixed $value): bool
    {
        return trim((string) $value) !== '';
    }

    private function outstandingStage(Hpp $hpp, int $outstanding): ?string
    {
        if ($outstanding <= 0) {
            return null;
        }

        if (! in_array($hpp->status, [Hpp::STATUS_DRAFT, Hpp::STATUS_IN_REVIEW, Hpp::STATUS_APPROVED], true)) {
            return null;
        }

        if ($hpp->status !== Hpp::STATUS_APPROVED) {
            return 'hpp';
        }

        $purchaseOrder = $hpp->purchaseOrder;
        $hasCompletePurchaseOrder = $purchaseOrder !== null
            && $this->hasValue($purchaseOrder->purchase_order_number)
            && $this->hasValue($purchaseOrder->po_document_path);

        if (! $hasCompletePurchaseOrder) {
            return 'hpp';
        }

        $parentBast = $hpp->lhppBasts->first();

        return $parentBast?->approval_status === LhppBast::APPROVAL_APPROVED
            ? 'lpj_process'
            : 'purchase_order';
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Hpp> */
    private function latestActiveHpps(
        ?string $costCategory,
        ?int $outlineAgreementId,
        ?int $year,
    ): \Illuminate\Database\Eloquent\Collection {
        return Hpp::query()
            ->when(
                $outlineAgreementId !== null,
                fn (Builder $query): Builder => $query->where('outline_agreement_id', $outlineAgreementId),
                fn (Builder $query): Builder => $query->whereHas('outlineAgreement', fn (Builder $agreementQuery): Builder => $agreementQuery
                    ->where('status', OutlineAgreement::STATUS_ACTIVE)),
            )
            ->when($year !== null, fn (Builder $query): Builder => $query->where(function (Builder $dateQuery) use ($year): void {
                $dateQuery
                    ->whereBetween('submitted_at', ["{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59"])
                    ->orWhere(function (Builder $draftDateQuery) use ($year): void {
                        $draftDateQuery
                            ->whereNull('submitted_at')
                            ->whereBetween('created_at', ["{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59"]);
                    });
            }))
            ->whereIn('status', [
                Hpp::STATUS_DRAFT,
                Hpp::STATUS_IN_REVIEW,
                Hpp::STATUS_APPROVED,
            ])
            ->when($costCategory !== null, fn (Builder $query): Builder => $query
                ->whereHas('budgetVerification', fn (Builder $verificationQuery): Builder => $verificationQuery
                    ->where('kategori_biaya', $costCategory)))
            ->whereNotExists(function ($query) use ($outlineAgreementId): void {
                $query
                    ->selectRaw('1')
                    ->from('hpps as newer_hpps')
                    ->whereColumn('newer_hpps.order_id', 'hpps.order_id')
                    ->when($outlineAgreementId !== null, fn ($newerQuery) => $newerQuery
                        ->where('newer_hpps.outline_agreement_id', $outlineAgreementId))
                    ->whereColumn('newer_hpps.id', '>', 'hpps.id');
            })
            ->with([
                'purchaseOrder:id,hpp_id,purchase_order_number,po_document_path',
                'lhppBasts' => fn ($query) => $query
                    ->where('termin_type', 'termin_1')
                    ->whereNull('parent_lhpp_bast_id')
                    ->latest('id')
                    ->select(['id', 'hpp_id', 'termin_type', 'parent_lhpp_bast_id', 'approval_status']),
            ])
            ->get(['id', 'order_id', 'total_keseluruhan', 'status']);
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
