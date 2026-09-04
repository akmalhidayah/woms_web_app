<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementMonthlyRealization;
use App\Models\OutlineAgreementTarget;
use App\Services\Admin\DashboardFinancialSummaryService;
use App\Services\Admin\DashboardTopTenHppCostService;
use App\Services\Admin\WorkshopDashboardService;
use App\Support\AdminActionCenter;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardFinancialSummaryService $financialSummaryService,
        private readonly DashboardTopTenHppCostService $topTenHppCostService,
        private readonly WorkshopDashboardService $workshopDashboardService,
        private readonly AdminActionCenter $actionCenter,
    ) {}

    public function __invoke(Request $request): View
    {
        $activeDashboard = $request->string('dashboard')->toString() === 'bengkel' ? 'bengkel' : 'jasa';
        $pendingActionCount = $this->actionCenter->pendingActionCount($request->user());
        $showActionSummaryBanner = (bool) $request->session()->pull(
            'show_admin_action_summary_banner',
            false,
        ) && $pendingActionCount > 0;
        $commonViewData = [
            'activeDashboard' => $activeDashboard,
            'showActionSummaryBanner' => $showActionSummaryBanner,
            'adminActionSummaryCount' => $pendingActionCount,
            'adminActionSummary' => $this->actionCenter->summary($request->user()),
        ];

        if ($activeDashboard === 'bengkel') {
            return view('dashboards.admin', $commonViewData + [
                'workshopDashboard' => $this->workshopDashboardService->resolve(
                    $request->integer('workshop_year') ?: null,
                    $request->query('workshop_month'),
                ),
            ]);
        }

        $context = $this->resolveDashboardContext($request);
        $agreementId = $context['outline_agreement_id'];
        $year = $context['year'];
        $financialSummary = $this->financialSummaryService->resolve(outlineAgreementId: $agreementId, year: $year);
        $maintenanceSummary = $this->financialSummaryService->resolveMaintenanceSummary($year, $agreementId);
        $nonMaintenanceSummary = $this->financialSummaryService->resolveForCategory('non pemeliharaan', $agreementId, $year);
        $capexSummary = $this->financialSummaryService->resolveForCategory('capex', $agreementId, $year);
        $totalPaguKontrak = $financialSummary['contract_budget'];
        $totalRealisasiSistem = $financialSummary['system_realization'];
        $totalRealisasiManual = $financialSummary['manual_realization'];
        $totalRealisasiBiaya = $financialSummary['realization'];
        $totalOutstandingBiaya = $financialSummary['outstanding'];
        $totalPrognosaBiaya = $financialSummary['prognosis'];
        $totalAnggaranTersedia = $financialSummary['available_budget'];

        return view('dashboards.admin', $commonViewData + [
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
            'maintenanceTargetYear' => $maintenanceSummary['target_year'],
            'maintenanceAnnualTarget' => $maintenanceSummary['annual_target'],
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
            'nonMaintenanceSummary' => $nonMaintenanceSummary,
            'capexSummary' => $capexSummary,
            'selectedOutlineAgreement' => $context['outline_agreement'],
            'selectedOutlineAgreementId' => $agreementId,
            'selectedDashboardYear' => $year,
            'dashboardOutlineAgreements' => $context['outline_agreements'],
            'dashboardAvailableYears' => $context['available_years'],
            'periodeKontrak' => $this->resolveOutlineAgreementPeriod($context['outline_agreement']),
            'realizationChartData' => $this->buildRealizationChartData($context['outline_agreement'], $year),
            'overhaulPrognosis' => $this->overhaulPrognosis($context['outline_agreement'], $year),
            'topTenCostSections' => $this->resolveTopTenCostSections($context['outline_agreement'], $year),
            'topTenMaintenanceCostSections' => $this->resolveTopTenCostSections($context['outline_agreement'], $year, 'pemeliharaan'),
        ]);
    }

    public function years(Request $request): JsonResponse
    {
        return response()->json($this->resolveDashboardContext($request)['available_years']);
    }

    public function realizationChart(Request $request): JsonResponse
    {
        $context = $this->resolveDashboardContext($request);
        $year = $context['year'];
        $startMonth = $this->normalizeMonth($request->integer('startMonth')) ?? 1;
        $endMonth = $this->normalizeMonth($request->integer('endMonth')) ?? 12;

        if ($request->boolean('includeTopTen')) {
            return response()->json([
                'realization' => $this->buildRealizationChartData($context['outline_agreement'], $year, $startMonth, $endMonth),
                'top_ten' => $this->resolveTopTenCostSections($context['outline_agreement'], $year),
                'top_ten_maintenance' => $this->resolveTopTenCostSections($context['outline_agreement'], $year, 'pemeliharaan'),
                'overhaul' => $this->overhaulPrognosis($context['outline_agreement'], $year),
            ]);
        }

        return response()->json($this->buildRealizationChartData($context['outline_agreement'], $year, $startMonth, $endMonth));
    }

    /**
     * @return array{
     *     outline_agreement: OutlineAgreement|null,
     *     outline_agreement_id: int|null,
     *     year: int|null,
     *     available_years: list<int>,
     *     outline_agreements: Collection<int, OutlineAgreement>
     * }
     */
    private function resolveDashboardContext(Request $request): array
    {
        $agreements = OutlineAgreement::query()
            ->whereIn('status', [
                OutlineAgreement::STATUS_ACTIVE,
                OutlineAgreement::STATUS_EXPIRED,
                OutlineAgreement::STATUS_CLOSED,
            ])
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [OutlineAgreement::STATUS_ACTIVE])
            ->orderByDesc('current_period_end')
            ->orderByDesc('id')
            ->get();
        $requestedAgreementId = $request->integer('oa_id');
        $selectedAgreement = $agreements->firstWhere('id', $requestedAgreementId)
            ?? $agreements->firstWhere('status', OutlineAgreement::STATUS_ACTIVE)
            ?? $agreements->first();
        $availableYears = $this->availableYearsForAgreement($selectedAgreement);
        $requestedYear = $request->integer('year');
        $selectedYear = in_array($requestedYear, $availableYears, true)
            ? $requestedYear
            : null;

        return [
            'outline_agreement' => $selectedAgreement,
            'outline_agreement_id' => $selectedAgreement?->id,
            'year' => $selectedYear,
            'available_years' => $availableYears,
            'outline_agreements' => $agreements,
        ];
    }

    /** @return list<int> */
    private function availableYearsForAgreement(?OutlineAgreement $agreement): array
    {
        if ($agreement === null) {
            return [(int) Carbon::now()->year];
        }

        $years = collect();
        if ($agreement->current_period_start && $agreement->current_period_end) {
            foreach (range($agreement->current_period_start->year, $agreement->current_period_end->year) as $year) {
                $years->push($year);
            }
        }

        $years = $years
            ->merge(OutlineAgreementTarget::query()
                ->where('outline_agreement_id', $agreement->id)
                ->pluck('tahun'))
            ->merge(OutlineAgreementMonthlyRealization::query()
                ->where('outline_agreement_id', $agreement->id)
                ->pluck('year'))
            ->merge(Hpp::query()
                ->where('outline_agreement_id', $agreement->id)
                ->get(['submitted_at', 'created_at'])
                ->map(fn (Hpp $hpp): int => ($hpp->submitted_at ?? $hpp->created_at)->year));

        return $years
            ->map(fn ($year): int => (int) $year)
            ->filter(fn (int $year): bool => $year > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function outlineAgreementMonthlyRealizationsQuery(?int $outlineAgreementId): Builder
    {
        return OutlineAgreementMonthlyRealization::query()
            ->when(
                $outlineAgreementId !== null,
                fn (Builder $query): Builder => $query->where('outline_agreement_id', $outlineAgreementId),
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            );
    }

    /**
     * @return array{start: string|null, end: string|null, adendum: string|null}
     */
    private function resolveOutlineAgreementPeriod(?OutlineAgreement $agreement): array
    {
        return [
            'start' => $agreement?->current_period_start?->toDateString(),
            'end' => $agreement?->current_period_end?->toDateString(),
            'adendum' => null,
        ];
    }

    /**
     * @return list<array{year: int, month: int, label: string, total: int, general: int, maintenance: int, non_maintenance: int, capex: int}>
     */
    private function buildRealizationChartData(
        ?OutlineAgreement $agreement,
        ?int $year,
        ?int $startMonth = null,
        ?int $endMonth = null,
    ): array {
        [$startDate, $endDate] = $this->resolveDashboardPeriod($agreement, $year, $startMonth, $endMonth);
        $outlineAgreementId = $agreement?->id;

        $transactionTotals = $this->financialSummaryService
            ->realizationEvents($startDate, $endDate, $outlineAgreementId)
            ->groupBy(fn (array $event): string => $event['date']->format('Y-m'))
            ->map(function ($group): array {
                return [
                    'general' => (int) $group->sum('amount'),
                    'maintenance' => (int) $group->where('category', 'pemeliharaan')->sum('amount'),
                    'non_maintenance' => (int) $group->where('category', 'non pemeliharaan')->sum('amount'),
                    'capex' => (int) $group->where('category', 'capex')->sum('amount'),
                ];
            });

        $monthlyTotals = $this->outlineAgreementMonthlyRealizationsQuery($outlineAgreementId)
            ->whereBetween('year', [$startDate->year, $endDate->year])
            ->where(function (Builder $query) use ($startDate, $endDate): void {
                $query
                    ->where(function (Builder $startQuery) use ($startDate): void {
                        $startQuery->where('year', '>', $startDate->year)
                            ->orWhere(fn (Builder $sameYear): Builder => $sameYear
                                ->where('year', $startDate->year)
                                ->where('month', '>=', $startDate->month));
                    })
                    ->where(function (Builder $endQuery) use ($endDate): void {
                        $endQuery->where('year', '<', $endDate->year)
                            ->orWhere(fn (Builder $sameYear): Builder => $sameYear
                                ->where('year', $endDate->year)
                                ->where('month', '<=', $endDate->month));
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
        ?OutlineAgreement $agreement,
        ?int $year,
        ?string $costCategory = null,
    ): array {
        [$periodStart, $periodEnd] = $this->resolveDashboardPeriod($agreement, $year);

        return $this->topTenHppCostService->resolve($periodStart, $periodEnd, $costCategory, $agreement?->id);
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function resolveDashboardPeriod(
        ?OutlineAgreement $agreement,
        ?int $year,
        ?int $startMonth = null,
        ?int $endMonth = null,
    ): array {
        $startMonth = $this->normalizeMonth($startMonth) ?? 1;
        $endMonth = $this->normalizeMonth($endMonth) ?? 12;
        $fallbackYear = (int) Carbon::now()->year;
        $startYear = $year ?? $agreement?->current_period_start?->year ?? $fallbackYear;
        $endYear = $year ?? $agreement?->current_period_end?->year ?? $fallbackYear;
        $startDate = Carbon::create($startYear, $startMonth, 1)->startOfDay();
        $endDate = Carbon::create($endYear, $endMonth, 1)->endOfMonth();

        if ($year === null && $agreement !== null) {
            $startDate = $startDate->max($agreement->current_period_start?->copy()->startOfDay() ?? $startDate);
            $endDate = $endDate->min($agreement->current_period_end?->copy()->endOfDay() ?? $endDate);
        }

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfMonth(), $startDate->copy()->endOfMonth()];
        }

        return [$startDate, $endDate];
    }

    /**
     * @return list<array{key: string, label: string, amount: int}>
     */
    private function overhaulPrognosis(
        ?OutlineAgreement $agreement,
        ?int $year,
    ): array {
        [$periodStart, $periodEnd] = $this->resolveDashboardPeriod($agreement, $year);
        $outlineAgreementId = $agreement?->id;

        $categories = [
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_2_3 => 'Tonasa 2/3',
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_4 => 'Tonasa 4',
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_5 => 'Tonasa 5',
        ];

        $totals = BudgetVerification::query()
            ->join('hpps', 'hpps.id', '=', 'budget_verifications.hpp_id')
            ->when(
                $outlineAgreementId !== null,
                fn (Builder $query): Builder => $query->where('hpps.outline_agreement_id', $outlineAgreementId),
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            )
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
