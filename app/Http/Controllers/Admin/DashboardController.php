<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Http\Controllers\Controller;
use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementMonthlyRealization;
use App\Models\PurchaseOrder;
use App\Services\Admin\DashboardFinancialSummaryService;
use App\Services\Admin\DashboardTopTenHppCostService;
use App\Support\AdminActionCenter;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** @var list<int>|null */
    private ?array $cachedRealizationYears = null;

    public function __construct(
        private readonly DashboardFinancialSummaryService $financialSummaryService,
        private readonly DashboardTopTenHppCostService $topTenHppCostService,
        private readonly AdminActionCenter $actionCenter,
    ) {}

    public function __invoke(Request $request): View
    {
        $financialSummary = $this->financialSummaryService->resolve();
        $maintenanceSummary = $this->financialSummaryService->resolveMaintenanceSummary(Carbon::now()->year);
        $nonMaintenanceSummary = $this->financialSummaryService->resolveForCategory('non pemeliharaan');
        $capexSummary = $this->financialSummaryService->resolveForCategory('capex');
        $totalPaguKontrak = $financialSummary['contract_budget'];
        $totalRealisasiSistem = $financialSummary['system_realization'];
        $totalRealisasiManual = $financialSummary['manual_realization'];
        $totalRealisasiBiaya = $financialSummary['realization'];
        $totalOutstandingBiaya = $financialSummary['outstanding'];
        $totalPrognosaBiaya = $financialSummary['prognosis'];
        $totalAnggaranTersedia = $financialSummary['available_budget'];
        $documentOnProcessHPPAmount = $this->sumPendingHppApprovalAmount();
        $approvalProcessHPPAmount = $this->sumApprovedHppsWaitingForPoAmount();
        $documentOnProcessPOAmount = $this->sumPurchaseOrdersWithNumberAndDocumentAmount();
        $monthlyRealizations = $this->sumActiveOutlineAgreementMonthlyRealizations();
        $documentPRPOAmount = $this->sumNormalLhppBastAmount() + $monthlyRealizations;
        $urgentAmount = $this->sumEmergencyLhppBastAmount();
        $totalAmount1 = $documentOnProcessHPPAmount + $approvalProcessHPPAmount + $documentOnProcessPOAmount;
        $totalAmount2 = $documentPRPOAmount + $urgentAmount;
        $totalSeluruhAmount = $totalAmount1 + $totalAmount2;
        $totalKuotaKontrak = $totalPaguKontrak;
        $budgetUsagePercentageHundredths = $financialSummary['prognosis_percentage_hundredths'];
        $pendingActionCount = $this->actionCenter->pendingActionCount($request->user());
        $showActionSummaryBanner = (bool) $request->session()->pull(
            'show_admin_action_summary_banner',
            false,
        ) && $pendingActionCount > 0;

        return view('dashboards.admin', [
            'outstandingNotifications' => $this->countOutstandingOrders(),
            'pendingProcessJasa' => $this->countPendingHppApprovals(),
            'approvalProcessHPPCount' => $this->countApprovedHppsWaitingForPo(),
            'documentOnProcessPOCount' => $this->countPurchaseOrdersWithNumberAndDocument(),
            'documentOnProcessHPPAmount' => $documentOnProcessHPPAmount,
            'approvalProcessHPPAmount' => $approvalProcessHPPAmount,
            'documentOnProcessPOAmount' => $documentOnProcessPOAmount,
            'documentPRPOAmount' => $documentPRPOAmount,
            'urgentAmount' => $urgentAmount,
            'totalAmount1' => $totalAmount1,
            'totalAmount2' => $totalAmount2,
            'totalPaguKontrak' => $totalPaguKontrak,
            'totalRealisasiSistem' => $totalRealisasiSistem,
            'totalRealisasiManual' => $totalRealisasiManual,
            'totalRealisasiBiaya' => $totalRealisasiBiaya,
            'totalOutstandingBiaya' => $totalOutstandingBiaya,
            'totalPrognosaBiaya' => $totalPrognosaBiaya,
            'totalAnggaranTersedia' => $totalAnggaranTersedia,
            'prognosaPercentageHundredths' => $financialSummary['prognosis_percentage_hundredths'],
            'realisasiPercentageHundredths' => $financialSummary['realization_percentage_hundredths'],
            'outstandingPercentageHundredths' => $financialSummary['outstanding_percentage_hundredths'],
            'anggaranTersediaPercentageHundredths' => $financialSummary['available_budget_percentage_hundredths'],
            'prognosaPercentageLabel' => $this->formatPercentageHundredths($financialSummary['prognosis_percentage_hundredths'], ','),
            'realisasiPercentageLabel' => $this->formatPercentageHundredths($financialSummary['realization_percentage_hundredths'], ','),
            'outstandingPercentageLabel' => $this->formatPercentageHundredths($financialSummary['outstanding_percentage_hundredths'], ','),
            'anggaranTersediaPercentageLabel' => $this->formatPercentageHundredths($financialSummary['available_budget_percentage_hundredths'], ','),
            'totalSeluruhAmount' => $totalSeluruhAmount,
            'totalPemakaianKuota' => $totalPrognosaBiaya,
            'budgetUsagePercentageHundredths' => $budgetUsagePercentageHundredths,
            'budgetUsagePercentageLabel' => $this->formatPercentageHundredths($budgetUsagePercentageHundredths, ','),
            'budgetUsageProgressWidth' => $this->formatPercentageHundredths(
                min(10000, max(0, $budgetUsagePercentageHundredths)),
                '.',
            ),
            'totalKuotaKontrak' => $totalKuotaKontrak,
            'sisaKuotaKontrak' => $totalAnggaranTersedia,
            'targetPemeliharaan' => $maintenanceSummary['annual_target'],
            'totalJasaPemeliharaan' => $maintenanceSummary['realization'],
            'sisaBiayaPemeliharaan' => $maintenanceSummary['remaining_target'],
            'maintenanceTargetYear' => $maintenanceSummary['target_year'],
            'maintenanceAnnualTarget' => $maintenanceSummary['annual_target'],
            'maintenanceSystemRealization' => $maintenanceSummary['system_realization'],
            'maintenanceManualRealization' => $maintenanceSummary['manual_realization'],
            'maintenanceRealization' => $maintenanceSummary['realization'],
            'maintenanceOutstanding' => $maintenanceSummary['outstanding'],
            'maintenancePrognosis' => $maintenanceSummary['prognosis'],
            'maintenanceRemainingTarget' => $maintenanceSummary['remaining_target'],
            'maintenanceTargetUsagePercentageHundredths' => $maintenanceSummary['target_usage_percentage_hundredths'],
            'maintenanceTargetUsagePercentageLabel' => $this->formatPercentageHundredths(
                $maintenanceSummary['target_usage_percentage_hundredths'],
                ',',
            ),
            'maintenanceTargetUsageProgressWidth' => $this->formatPercentageHundredths(
                min(10000, max(0, $maintenanceSummary['target_usage_percentage_hundredths'])),
                '.',
            ),
            'maintenanceLpjStatusAmount' => $maintenanceSummary['lpj_status_amount'],
            'maintenanceInvoiceStatusAmount' => $maintenanceSummary['invoice_status_amount'],
            'maintenanceLpjPplInProcess' => $maintenanceSummary['lpj_status_amount'],
            'maintenanceAlreadyRealized' => $maintenanceSummary['already_realized'],
            'nonMaintenanceSummary' => $nonMaintenanceSummary,
            'capexSummary' => $capexSummary,
            'periodeKontrak' => $this->resolveActiveOutlineAgreementPeriod(),
            'realizationYears' => $this->realizationYearsList(),
            'realizationChartData' => $this->buildRealizationChartData(),
            'overhaulPrognosis' => $this->overhaulPrognosis(),
            'topTenCostSections' => $this->resolveTopTenCostSections(),
            'topTenMaintenanceCostSections' => $this->resolveTopTenCostSections(costCategory: 'pemeliharaan'),
            'showActionSummaryBanner' => $showActionSummaryBanner,
            'adminActionSummaryCount' => $pendingActionCount,
            'adminActionSummary' => $this->actionCenter->summary($request->user()),
        ]);
    }

    public function years(): JsonResponse
    {
        return response()->json($this->realizationYearsList());
    }

    public function realizationChart(Request $request): JsonResponse
    {
        $parameters = [
            $request->integer('startYear') ?: null,
            $request->integer('endYear') ?: null,
            $request->integer('startMonth') ?: null,
            $request->integer('endMonth') ?: null,
        ];

        if ($request->boolean('includeTopTen')) {
            return response()->json([
                'realization' => $this->buildRealizationChartData(...$parameters),
                'top_ten' => $this->resolveTopTenCostSections(...$parameters),
                'top_ten_maintenance' => $this->resolveTopTenCostSections(
                    $parameters[0],
                    $parameters[1],
                    $parameters[2],
                    $parameters[3],
                    'pemeliharaan',
                ),
                'overhaul' => $this->overhaulPrognosis(...$parameters),
            ]);
        }

        return response()->json($this->buildRealizationChartData(...$parameters));
    }

    private function countOutstandingOrders(): int
    {
        return Order::query()
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->whereHas('scopeOfWork')
            ->doesntHave('hpps')
            ->count();
    }

    private function countPendingHppApprovals(): int
    {
        return Hpp::query()
            ->whereHas('order')
            ->where('status', Hpp::STATUS_IN_REVIEW)
            ->whereNotNull('submitted_at')
            ->count();
    }

    private function countApprovedHppsWaitingForPo(): int
    {
        return Hpp::query()
            ->whereHas('order')
            ->where('status', Hpp::STATUS_APPROVED)
            ->whereDoesntHave('purchaseOrder', fn (Builder $query) => $query
                ->whereNotNull('purchase_order_number')
                ->whereRaw("TRIM(purchase_order_number) <> ''"))
            ->count();
    }

    private function countPurchaseOrdersWithNumberAndDocument(): int
    {
        return PurchaseOrder::query()
            ->whereHas('order')
            ->whereHas('hpp', fn (Builder $query) => $query->whereHas('order'))
            ->whereNotNull('purchase_order_number')
            ->whereRaw("TRIM(purchase_order_number) <> ''")
            ->whereNotNull('po_document_path')
            ->whereRaw("TRIM(po_document_path) <> ''")
            ->count();
    }

    private function sumPendingHppApprovalAmount(): int
    {
        return $this->moneyInt(Hpp::query()
            ->whereHas('order')
            ->where('status', Hpp::STATUS_IN_REVIEW)
            ->whereNotNull('submitted_at')
            ->sum('total_keseluruhan'));
    }

    private function sumApprovedHppsWaitingForPoAmount(): int
    {
        return $this->moneyInt(Hpp::query()
            ->whereHas('order')
            ->where('status', Hpp::STATUS_APPROVED)
            ->whereDoesntHave('purchaseOrder', fn (Builder $query) => $query
                ->whereNotNull('purchase_order_number')
                ->whereRaw("TRIM(purchase_order_number) <> ''")
                ->whereNotNull('po_document_path')
                ->whereRaw("TRIM(po_document_path) <> ''"))
            ->sum('total_keseluruhan'));
    }

    private function sumPurchaseOrdersWithNumberAndDocumentAmount(): int
    {
        return $this->moneyInt(Hpp::query()
            ->whereHas('order')
            ->whereHas('purchaseOrder', fn (Builder $query) => $query
                ->whereHas('order')
                ->whereNotNull('purchase_order_number')
                ->whereRaw("TRIM(purchase_order_number) <> ''")
                ->whereNotNull('po_document_path')
                ->whereRaw("TRIM(po_document_path) <> ''"))
            ->sum('total_keseluruhan'));
    }

    private function sumNormalLhppBastAmount(): int
    {
        return $this->moneyInt($this->baseLhppBastRealizationQuery()
            ->whereHas('order', fn (Builder $query) => $query->whereNotIn('prioritas', $this->emergencyPriorities()))
            ->whereNotNull('purchase_order_number')
            ->whereRaw("TRIM(purchase_order_number) <> ''")
            ->sum('total_aktual_biaya'));
    }

    private function sumEmergencyLhppBastAmount(): int
    {
        return $this->moneyInt($this->baseLhppBastRealizationQuery()
            ->whereHas('order', fn (Builder $query) => $query->whereIn('prioritas', $this->emergencyPriorities()))
            ->sum('total_aktual_biaya'));
    }

    private function sumActiveOutlineAgreementMonthlyRealizations(): int
    {
        return (int) $this->activeOutlineAgreementMonthlyRealizationsQuery()->sum('amount');
    }

    private function activeOutlineAgreementMonthlyRealizationsQuery(): Builder
    {
        return OutlineAgreementMonthlyRealization::query()
            ->whereHas('outlineAgreement', fn (Builder $query) => $query
                ->where('status', OutlineAgreement::STATUS_ACTIVE));
    }

    private function baseLhppBastRealizationQuery(): Builder
    {
        return LhppBast::query()
            ->whereHas('order')
            ->where('termin_type', 'termin_1')
            ->whereNull('parent_lhpp_bast_id');
    }

    /**
     * @return list<string>
     */
    private function emergencyPriorities(): array
    {
        return [
            Order::PRIORITY_URGENT,
            Order::PRIORITY_HIGH,
        ];
    }

    /**
     * @return array{start: string|null, end: string|null, adendum: string|null}
     */
    private function resolveActiveOutlineAgreementPeriod(): array
    {
        $start = OutlineAgreement::query()
            ->where('status', OutlineAgreement::STATUS_ACTIVE)
            ->min('current_period_start');
        $end = OutlineAgreement::query()
            ->where('status', OutlineAgreement::STATUS_ACTIVE)
            ->max('current_period_end');

        return [
            'start' => $start ?: null,
            'end' => $end ?: null,
            'adendum' => null,
        ];
    }

    /**
     * @return list<int>
     */
    private function realizationYearsList(): array
    {
        if ($this->cachedRealizationYears !== null) {
            return $this->cachedRealizationYears;
        }

        $transactionYears = $this->baseLhppBastRealizationQuery()
            ->whereNotNull('tanggal_bast')
            ->pluck('tanggal_bast')
            ->map(fn ($date): int => Carbon::parse($date)->year);
        $outlineAgreementYears = $this->activeOutlineAgreementMonthlyRealizationsQuery()
            ->distinct()
            ->pluck('year')
            ->map(fn ($year): int => (int) $year);
        $submittedHppYears = Hpp::query()
            ->whereNotNull('submitted_at')
            ->whereIn('status', [
                Hpp::STATUS_IN_REVIEW,
                Hpp::STATUS_APPROVED,
                Hpp::STATUS_REJECTED,
            ])
            ->pluck('submitted_at')
            ->map(fn ($date): int => Carbon::parse($date)->year);

        return $this->cachedRealizationYears = $transactionYears
            ->merge($outlineAgreementYears)
            ->merge($submittedHppYears)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return list<array{year: int, month: int, label: string, total: int, general: int, maintenance: int, non_maintenance: int, capex: int}>
     */
    private function buildRealizationChartData(
        ?int $startYear = null,
        ?int $endYear = null,
        ?int $startMonth = null,
        ?int $endMonth = null,
    ): array {
        [$startDate, $endDate] = $this->resolveDashboardPeriod($startYear, $endYear, $startMonth, $endMonth);

        $filterStartYear = $startDate->year;
        $filterStartMonth = $startDate->month;
        $filterEndYear = $endDate->year;
        $filterEndMonth = $endDate->month;

        $transactionTotals = $this->financialSummaryService
            ->realizationEvents($startDate, $endDate)
            ->groupBy(fn (array $event): string => $event['date']->format('Y-m'))
            ->map(function ($group): array {
                return [
                    'general' => (int) $group->sum('amount'),
                    'maintenance' => (int) $group->where('category', 'pemeliharaan')->sum('amount'),
                    'non_maintenance' => (int) $group->where('category', 'non pemeliharaan')->sum('amount'),
                    'capex' => (int) $group->where('category', 'capex')->sum('amount'),
                ];
            });

        $monthlyTotals = $this->activeOutlineAgreementMonthlyRealizationsQuery()
            ->where(function (Builder $query) use ($filterStartYear, $filterStartMonth): void {
                $query
                    ->where('year', '>', $filterStartYear)
                    ->orWhere(function (Builder $periodQuery) use ($filterStartYear, $filterStartMonth): void {
                        $periodQuery
                            ->where('year', $filterStartYear)
                            ->where('month', '>=', $filterStartMonth);
                    });
            })
            ->where(function (Builder $query) use ($filterEndYear, $filterEndMonth): void {
                $query
                    ->where('year', '<', $filterEndYear)
                    ->orWhere(function (Builder $periodQuery) use ($filterEndYear, $filterEndMonth): void {
                        $periodQuery
                            ->where('year', $filterEndYear)
                            ->where('month', '<=', $filterEndMonth);
                    });
            })
            ->selectRaw('year, month, kategori_biaya, SUM(amount) as total_amount')
            ->groupBy('year', 'month', 'kategori_biaya')
            ->get()
            ->groupBy(fn (OutlineAgreementMonthlyRealization $row): string => sprintf('%04d-%02d', $row->year, $row->month));

        $months = collect();
        for ($cursor = $startDate->copy()->startOfMonth(); $cursor->lte($endDate); $cursor->addMonth()) {
            $months->push($cursor->copy());
        }

        return $months
            ->map(function (Carbon $monthDate) use ($transactionTotals, $monthlyTotals): array {
                $key = $monthDate->format('Y-m');
                $transaction = $transactionTotals->get($key, [
                    'general' => 0,
                    'maintenance' => 0,
                    'non_maintenance' => 0,
                    'capex' => 0,
                ]);
                $manualRows = $monthlyTotals->get($key, collect());
                $manualGeneral = (int) $manualRows->sum('total_amount');
                $manualByCategory = fn (string $category): int => (int) $manualRows
                    ->where('kategori_biaya', $category)
                    ->sum('total_amount');

                $general = (int) $transaction['general'] + $manualGeneral;

                return [
                    'year' => $monthDate->year,
                    'month' => $monthDate->month,
                    'label' => $monthDate->translatedFormat('M Y'),
                    'total' => $general,
                    'general' => $general,
                    'maintenance' => (int) $transaction['maintenance'] + $manualByCategory('pemeliharaan'),
                    'non_maintenance' => (int) $transaction['non_maintenance'] + $manualByCategory('non pemeliharaan'),
                    'capex' => (int) $transaction['capex'] + $manualByCategory('capex'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{section: string, amount: int}>
     */
    private function resolveTopTenCostSections(
        ?int $startYear = null,
        ?int $endYear = null,
        ?int $startMonth = null,
        ?int $endMonth = null,
        ?string $costCategory = null,
    ): array {
        [$periodStart, $periodEnd] = $this->resolveDashboardPeriod(
            $startYear,
            $endYear,
            $startMonth,
            $endMonth,
        );

        return $this->topTenHppCostService->resolve($periodStart, $periodEnd, $costCategory);
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function resolveDashboardPeriod(
        ?int $startYear = null,
        ?int $endYear = null,
        ?int $startMonth = null,
        ?int $endMonth = null,
    ): array {
        $availableYears = $this->realizationYearsList();
        $startYear ??= $availableYears[0] ?? (int) Carbon::now()->year;
        $endYear ??= $availableYears[array_key_last($availableYears)] ?? $startYear;
        $startMonth = $this->normalizeMonth($startMonth) ?? 1;
        $endMonth = $this->normalizeMonth($endMonth) ?? 12;

        if ($startYear > $endYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
        }

        $startDate = Carbon::create($startYear, $startMonth, 1)->startOfDay();
        $endDate = Carbon::create($endYear, $endMonth, 1)->endOfMonth();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfMonth(), $startDate->copy()->endOfMonth()];
        }

        return [$startDate, $endDate];
    }

    /**
     * @return list<array{key: string, label: string, amount: int}>
     */
    private function overhaulPrognosis(
        ?int $startYear = null,
        ?int $endYear = null,
        ?int $startMonth = null,
        ?int $endMonth = null,
    ): array {
        [$periodStart, $periodEnd] = $this->resolveDashboardPeriod(
            $startYear,
            $endYear,
            $startMonth,
            $endMonth,
        );

        $categories = [
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_2_3 => 'Tonasa 2/3',
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_4 => 'Tonasa 4',
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_5 => 'Tonasa 5',
        ];

        $totals = BudgetVerification::query()
            ->join('hpps', 'hpps.id', '=', 'budget_verifications.hpp_id')
            ->where('hpps.status', Hpp::STATUS_APPROVED)
            ->whereNotNull('hpps.submitted_at')
            ->whereBetween('hpps.submitted_at', [$periodStart, $periodEnd])
            ->whereIn('budget_verifications.status_anggaran', [
                BudgetVerification::STATUS_AVAILABLE,
                BudgetVerification::STATUS_WAITING_BAST,
            ])
            ->whereIn('budget_verifications.kategori_biaya', array_keys($categories))
            ->selectRaw('budget_verifications.kategori_biaya as category_key, SUM(hpps.total_keseluruhan) as aggregate_amount')
            ->groupBy('budget_verifications.kategori_biaya')
            ->pluck('aggregate_amount', 'category_key');

        return collect($categories)
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
                'amount' => $this->moneyInt($totals->get($key, 0)),
            ])
            ->values()
            ->all();
    }

    private function normalizeMonth(?int $month): ?int
    {
        if ($month === null || $month < 1 || $month > 12) {
            return null;
        }

        return $month;
    }

    private function formatPercentageHundredths(int $value, string $decimalSeparator): string
    {
        $sign = $value < 0 ? '-' : '';
        $absoluteValue = abs($value);
        $whole = intdiv($absoluteValue, 100);
        $fraction = $absoluteValue % 100;

        if ($fraction === 0) {
            return $sign.$whole;
        }

        return $sign.$whole.$decimalSeparator.rtrim(str_pad((string) $fraction, 2, '0', STR_PAD_LEFT), '0');
    }

    private function moneyInt(mixed $value): int
    {
        return (int) round((float) $value);
    }
}
