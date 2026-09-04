<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\QualityControlReport;
use App\Models\QualityControlSignature;
use App\Support\PkmJobWaitingQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class WorkshopDashboardService
{
    public const COMPLETION_TARGET = 90;

    /**
     * @return array<string, mixed>
     */
    public function resolve(?int $requestedYear = null, int|string|null $requestedMonth = null): array
    {
        $availableYears = $this->availableYears();
        $currentYear = (int) Carbon::now()->year;
        $year = $requestedYear !== null && in_array($requestedYear, $availableYears, true)
            ? $requestedYear
            : $currentYear;
        $month = $this->normalizeMonth($requestedMonth);
        $periodQuery = $this->baseQuery()
            ->whereYear('orders.tanggal_order', $year)
            ->when($month !== null, fn (Builder $query): Builder => $query
                ->whereMonth('orders.tanggal_order', $month));
        $estimatorWorkshopOrderIds = $this->estimatorWorkshopOrderIds(clone $periodQuery);
        $jobWaitingEstimator = $this->jobWaitingEstimatorMetric($year, $month, $estimatorWorkshopOrderIds);
        $summary = $this->combinedSummary(
            $this->aggregate(clone $periodQuery),
            $jobWaitingEstimator,
        );
        $reguRows = $this->reguSummary(clone $periodQuery, $jobWaitingEstimator);
        $trend = $this->completionTrend($year, $month);

        return [
            'filters' => [
                'year' => $year,
                'month' => $month,
            ],
            'available_years' => $availableYears,
            'summary' => $summary,
            'regu' => $reguRows['items'],
            'unknown_regu_count' => $reguRows['unknown_count'],
            'has_orders' => $summary['total'] > 0,
            'trend_has_orders' => collect($trend)->contains(fn (array $row): bool => $row['total'] > 0),
            'trend' => $trend,
            'monthly_costs' => $this->monthlyCosts($year, $month),
        ];
    }

    /** @return list<int> */
    public function availableYears(): array
    {
        $yearExpression = $this->datePartExpression('year', 'orders.tanggal_order');
        $workshopYears = $this->baseQuery()
            ->selectRaw("{$yearExpression} as dashboard_year")
            ->whereNotNull('orders.tanggal_order')
            ->distinct()
            ->pluck('dashboard_year')
            ->map(fn ($year): int => (int) $year)
            ->filter(fn (int $year): bool => $year > 0);
        $jobWaitingYears = PkmJobWaitingQuery::applyEntryEligibility(Order::query())
            ->selectRaw("{$yearExpression} as dashboard_year")
            ->whereNotNull('orders.tanggal_order')
            ->distinct()
            ->pluck('dashboard_year')
            ->map(fn ($year): int => (int) $year)
            ->filter(fn (int $year): bool => $year > 0);

        $years = $workshopYears
            ->merge($jobWaitingYears)
            ->push((int) Carbon::now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return $years;
    }

    private function baseQuery(): Builder
    {
        return Order::query()
            ->join('order_workshops', 'order_workshops.order_id', '=', 'orders.id')
            ->leftJoinSub(
                DB::table('quality_control_reports')
                    ->selectRaw('order_id, MAX(id) AS report_id')
                    ->groupBy('order_id'),
                'dashboard_latest_qc',
                'dashboard_latest_qc.order_id',
                '=',
                'orders.id',
            )
            ->leftJoin('quality_control_reports as dashboard_qc', 'dashboard_qc.id', '=', 'dashboard_latest_qc.report_id')
            ->leftJoinSub(
                $this->qualityControlSignatureSummaryQuery(),
                'dashboard_qc_signatures',
                'dashboard_qc_signatures.quality_control_report_id',
                '=',
                'dashboard_qc.id',
            )
            ->whereIn('orders.catatan_status', [
                OrderUserNoteStatus::ApprovedWorkshop->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ]);
    }

    /**
     * @return array{
     *     total: int,
     *     in_progress: int,
     *     completed: int,
     *     incomplete: int,
     *     completion_percentage: float,
     *     completion_percentage_hundredths: int,
     *     completion_target: int,
     *     target_met: bool,
     *     total_cost: int
     * }
     */
    private function aggregate(Builder $query): array
    {
        $completedExpression = $this->completedExpression();
        $row = $query
            ->selectRaw('COUNT(orders.id) as total_order')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$completedExpression} THEN 1 ELSE 0 END), 0) as completed_order",
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN order_workshops.progress_status IN (?, ?) AND NOT {$completedExpression} THEN 1 ELSE 0 END), 0) as in_progress_order",
                [
                    OrderWorkshop::PROGRESS_IN_PROGRESS,
                    OrderWorkshop::PROGRESS_QUALITY_CONTROL,
                ],
            )
            ->selectRaw('COALESCE(SUM(orders.biaya), 0) as total_cost')
            ->first();

        return $this->metric(
            (int) ($row?->getAttribute('total_order') ?? 0),
            (int) ($row?->getAttribute('in_progress_order') ?? 0),
            (int) ($row?->getAttribute('completed_order') ?? 0),
            $this->moneyInt($row?->getAttribute('total_cost')),
        );
    }

    /**
     * @return array{items: list<array<string, int|float|string|bool>>, unknown_count: int}
     */
    private function reguSummary(Builder $query, array $jobWaitingEstimator): array
    {
        $reguExpression = "TRIM(COALESCE(orders.catatan, ''))";
        $completedExpression = $this->completedExpression();
        $rows = $query
            ->selectRaw("{$reguExpression} as regu")
            ->selectRaw('COUNT(orders.id) as total_order')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$completedExpression} THEN 1 ELSE 0 END), 0) as completed_order",
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN order_workshops.progress_status IN (?, ?) AND NOT {$completedExpression} THEN 1 ELSE 0 END), 0) as in_progress_order",
                [
                    OrderWorkshop::PROGRESS_IN_PROGRESS,
                    OrderWorkshop::PROGRESS_QUALITY_CONTROL,
                ],
            )
            ->groupByRaw($reguExpression)
            ->get()
            ->keyBy(fn (Order $row): string => trim((string) $row->getAttribute('regu')));
        $officialRegu = Order::workshopReguOptions();
        $items = collect($officialRegu)
            ->map(function (string $regu) use ($rows, $jobWaitingEstimator): array {
                $row = $rows->get($regu);
                $total = (int) ($row?->getAttribute('total_order') ?? 0);
                $inProgress = (int) ($row?->getAttribute('in_progress_order') ?? 0);
                $completed = (int) ($row?->getAttribute('completed_order') ?? 0);

                if ($regu === Order::WORKSHOP_REGU_ESTIMATOR) {
                    $total += $jobWaitingEstimator['total'];
                    $inProgress += $jobWaitingEstimator['in_progress'];
                    $completed += $jobWaitingEstimator['completed'];
                }

                return ['name' => $regu] + $this->metric(
                    $total,
                    $inProgress,
                    $completed,
                    0,
                );
            })
            ->values()
            ->all();
        $unknownCount = $rows
            ->reject(fn (Order $row, string $regu): bool => in_array($regu, $officialRegu, true))
            ->sum(fn (Order $row): int => (int) $row->getAttribute('total_order'));

        return [
            'items' => $items,
            'unknown_count' => (int) $unknownCount,
        ];
    }

    /** @return list<int> */
    private function estimatorWorkshopOrderIds(Builder $query): array
    {
        $reguExpression = "TRIM(COALESCE(orders.catatan, ''))";

        return $query
            ->whereRaw("{$reguExpression} = ?", [Order::WORKSHOP_REGU_ESTIMATOR])
            ->distinct()
            ->pluck('orders.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<string, int|float|bool>  $workshopSummary
     * @param  array{total: int, in_progress: int, completed: int}  $jobWaitingEstimator
     * @return array<string, int|float|bool>
     */
    private function combinedSummary(array $workshopSummary, array $jobWaitingEstimator): array
    {
        return $this->metric(
            (int) $workshopSummary['total'] + $jobWaitingEstimator['total'],
            (int) $workshopSummary['in_progress'] + $jobWaitingEstimator['in_progress'],
            (int) $workshopSummary['completed'] + $jobWaitingEstimator['completed'],
            (int) $workshopSummary['total_cost'],
        ) + [
            'outsourced' => $jobWaitingEstimator['total'],
        ];
    }

    /**
     * @param  list<int>  $excludedOrderIds
     * @return array{total: int, in_progress: int, completed: int}
     */
    private function jobWaitingEstimatorMetric(int $year, ?int $month, array $excludedOrderIds): array
    {
        $orders = PkmJobWaitingQuery::applyEntryEligibility(Order::query())
            ->with([
                'latestPurchaseOrder' => fn ($query) => $query->select([
                    'purchase_orders.id',
                    'purchase_orders.order_id',
                    'purchase_orders.approve_manager',
                    'purchase_orders.purchase_order_number',
                    'purchase_orders.progress_pekerjaan',
                ]),
                'initialWork' => fn ($query) => $query->select([
                    'initial_works.id',
                    'initial_works.order_id',
                    'initial_works.progress_pekerjaan',
                ]),
            ])
            ->whereYear('orders.tanggal_order', $year)
            ->when($month !== null, fn (Builder $query): Builder => $query
                ->whereMonth('orders.tanggal_order', $month))
            ->when($excludedOrderIds !== [], fn (Builder $query): Builder => $query
                ->whereNotIn('orders.id', $excludedOrderIds))
            ->get(['orders.id', 'orders.prioritas']);
        $progressValues = $orders->map(fn (Order $order): int => $this->jobWaitingProgress($order));

        return [
            'total' => $orders->count(),
            'in_progress' => $progressValues
                ->filter(fn (int $progress): bool => $progress >= 11 && $progress < 100)
                ->count(),
            'completed' => $progressValues
                ->filter(fn (int $progress): bool => $progress >= 100)
                ->count(),
        ];
    }

    private function jobWaitingProgress(Order $order): int
    {
        $purchaseOrder = $order->latestPurchaseOrder;
        $initialWork = $order->initialWork;
        $hasValidPurchaseOrder = $purchaseOrder !== null
            && $purchaseOrder->approve_manager
            && filled($purchaseOrder->purchase_order_number);
        $usesInitialWork = ! $hasValidPurchaseOrder
            && in_array($order->prioritas, [Order::PRIORITY_URGENT, Order::PRIORITY_HIGH], true)
            && $initialWork !== null;
        $progress = $hasValidPurchaseOrder
            ? (int) $purchaseOrder->progress_pekerjaan
            : ($usesInitialWork ? (int) $initialWork->progress_pekerjaan : 0);

        return max(0, min(100, $progress));
    }

    /**
     * @return list<array{month: int, label: string, total: int, completed: int, percentage: float, target: int}>
     */
    private function completionTrend(int $year, ?int $selectedMonth): array
    {
        $lastMonth = $this->lastTrendMonth($year, $selectedMonth);
        $orderMonthExpression = $this->datePartExpression('month', 'orders.tanggal_order');
        $completionTimestampExpression = $this->completionTimestampExpression();
        $trendEnd = Carbon::create($year, $lastMonth, 1)->endOfMonth();
        $monthlyOrders = $this->baseQuery()
            ->whereYear('orders.tanggal_order', $year)
            ->whereMonth('orders.tanggal_order', '<=', $lastMonth)
            ->selectRaw("{$orderMonthExpression} as order_month, COUNT(orders.id) as total_order")
            ->groupByRaw($orderMonthExpression)
            ->pluck('total_order', 'order_month')
            ->map(fn ($total): int => (int) $total);
        $completionRows = $this->baseQuery()
            ->whereYear('orders.tanggal_order', $year)
            ->whereMonth('orders.tanggal_order', '<=', $lastMonth)
            ->selectRaw('orders.id as order_id')
            ->selectRaw("{$orderMonthExpression} as order_month")
            ->selectRaw("{$completionTimestampExpression} as completion_at");
        $completionYearExpression = $this->datePartExpression('year', 'dashboard_completion_rows.completion_at');
        $completionMonthExpression = $this->datePartExpression('month', 'dashboard_completion_rows.completion_at');
        $completionGroups = DB::query()
            ->fromSub($completionRows, 'dashboard_completion_rows')
            ->whereNotNull('dashboard_completion_rows.completion_at')
            ->where('dashboard_completion_rows.completion_at', '<=', $trendEnd)
            ->select('dashboard_completion_rows.order_month')
            ->selectRaw("{$completionYearExpression} as completion_year")
            ->selectRaw("{$completionMonthExpression} as completion_month")
            ->selectRaw('COUNT(*) as total_order')
            ->groupBy('dashboard_completion_rows.order_month')
            ->groupByRaw("{$completionYearExpression}, {$completionMonthExpression}")
            ->get();
        $cumulativeTotal = 0;

        return collect(range(1, $lastMonth))
            ->map(function (int $month) use ($year, $monthlyOrders, $completionGroups, &$cumulativeTotal): array {
                $cumulativeTotal += (int) $monthlyOrders->get($month, 0);
                $completed = $completionGroups->sum(function (object $row) use ($year, $month): int {
                    $orderMonth = (int) $row->order_month;
                    $completionYear = (int) $row->completion_year;
                    $completionMonth = (int) $row->completion_month;
                    $completedByMonth = $completionYear < $year
                        || ($completionYear === $year && $completionMonth <= $month);

                    return $orderMonth <= $month && $completedByMonth
                        ? (int) $row->total_order
                        : 0;
                });
                $percentage = $this->percentageHundredths((int) $completed, $cumulativeTotal) / 100.0;

                return [
                    'month' => $month,
                    'label' => $this->monthLabel($month),
                    'total' => $cumulativeTotal,
                    'completed' => (int) $completed,
                    'percentage' => $percentage,
                    'target' => self::COMPLETION_TARGET,
                ];
            })
            ->all();
    }

    /**
     * @return list<array{month: int, label: string, amount: int}>
     */
    private function monthlyCosts(int $year, ?int $selectedMonth): array
    {
        $firstMonth = $selectedMonth ?? 1;
        $lastMonth = $selectedMonth ?? 12;
        $monthExpression = $this->datePartExpression('month', 'orders.tanggal_order');
        $totals = $this->baseQuery()
            ->whereYear('orders.tanggal_order', $year)
            ->when($selectedMonth !== null, fn (Builder $query): Builder => $query
                ->whereMonth('orders.tanggal_order', $selectedMonth))
            ->selectRaw("{$monthExpression} as order_month, COALESCE(SUM(orders.biaya), 0) as total_cost")
            ->groupByRaw($monthExpression)
            ->get()
            ->mapWithKeys(fn (Order $row): array => [
                (int) $row->getAttribute('order_month') => $this->moneyInt($row->getAttribute('total_cost')),
            ]);

        return collect(range($firstMonth, $lastMonth))
            ->map(fn (int $month): array => [
                'month' => $month,
                'label' => $this->monthLabel($month),
                'amount' => (int) $totals->get($month, 0),
            ])
            ->all();
    }

    /**
     * @return array{
     *     total: int,
     *     in_progress: int,
     *     completed: int,
     *     incomplete: int,
     *     completion_percentage: float,
     *     completion_percentage_hundredths: int,
     *     completion_target: int,
     *     target_met: bool,
     *     total_cost: int
     * }
     */
    private function metric(int $total, int $inProgress, int $completed, int $totalCost): array
    {
        $percentageHundredths = $this->percentageHundredths($completed, $total);

        return [
            'total' => $total,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'incomplete' => max($total - $completed, 0),
            'completion_percentage' => $percentageHundredths / 100.0,
            'completion_percentage_hundredths' => $percentageHundredths,
            'completion_target' => self::COMPLETION_TARGET,
            'target_met' => $percentageHundredths >= (self::COMPLETION_TARGET * 100),
            'total_cost' => $totalCost,
        ];
    }

    private function normalizeMonth(int|string|null $month): ?int
    {
        if ($month === null || $month === '' || $month === 'all') {
            return null;
        }

        $normalized = filter_var($month, FILTER_VALIDATE_INT);

        return is_int($normalized) && $normalized >= 1 && $normalized <= 12
            ? $normalized
            : (int) Carbon::now()->month;
    }

    private function lastTrendMonth(int $year, ?int $selectedMonth): int
    {
        $lastMonth = $selectedMonth ?? 12;

        if ($year === (int) Carbon::now()->year) {
            $lastMonth = min($lastMonth, (int) Carbon::now()->month);
        }

        return max(1, $lastMonth);
    }

    private function percentageHundredths(int $value, int $total): int
    {
        return $total > 0 ? (int) round(($value * 10000) / $total) : 0;
    }

    private function moneyInt(mixed $value): int
    {
        return is_numeric($value) ? (int) round((float) $value) : 0;
    }

    private function datePartExpression(string $part, string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $format = $part === 'year' ? '%Y' : '%m';

            return "CAST(strftime('{$format}', {$column}) AS INTEGER)";
        }

        return strtoupper($part)."({$column})";
    }

    private function completedExpression(): string
    {
        return sprintf(
            '(order_workshops.legacy_completed_at IS NOT NULL OR %s OR %s)',
            $this->nonCriticalCompletedExpression(),
            $this->qualityControlCompletedExpression(),
        );
    }

    private function completionTimestampExpression(): string
    {
        $qualityControlCompleted = $this->qualityControlCompletedExpression();
        $nonCriticalCompleted = $this->nonCriticalCompletedExpression();

        return "(CASE
            WHEN order_workshops.legacy_completed_at IS NOT NULL THEN order_workshops.legacy_completed_at
            WHEN {$qualityControlCompleted} THEN COALESCE(
                dashboard_qc_signatures.last_signed_at,
                dashboard_qc.updated_at
            )
            WHEN {$nonCriticalCompleted} THEN order_workshops.updated_at
            ELSE NULL
        END)";
    }

    private function nonCriticalCompletedExpression(): string
    {
        return "(dashboard_qc.id IS NULL AND order_workshops.progress_status = '".OrderWorkshop::PROGRESS_DONE."')";
    }

    private function qualityControlCompletedExpression(): string
    {
        $makerSignature = $this->qualityControlMakerSignatureExpression();
        $submitted = QualityControlReport::STATUS_SUBMITTED;

        return "(dashboard_qc.id IS NOT NULL
            AND dashboard_qc.status = '{$submitted}'
            AND {$makerSignature} <> ''
            AND COALESCE(dashboard_qc_signatures.signature_count, 0) = 2
            AND COALESCE(dashboard_qc_signatures.workshop_signed_count, 0) = 1
            AND COALESCE(dashboard_qc_signatures.user_signed_count, 0) = 1)";
    }

    private function qualityControlSignatureSummaryQuery(): QueryBuilder
    {
        return DB::table('quality_control_signatures')
            ->select('quality_control_report_id')
            ->selectRaw('COUNT(*) AS signature_count')
            ->selectRaw(
                'SUM(CASE WHEN role_key = ? AND status = ? THEN 1 ELSE 0 END) AS workshop_signed_count',
                [QualityControlSignature::ROLE_WORKSHOP_MANAGER, QualityControlSignature::STATUS_SIGNED],
            )
            ->selectRaw(
                'SUM(CASE WHEN role_key = ? AND status = ? THEN 1 ELSE 0 END) AS user_signed_count',
                [QualityControlSignature::ROLE_USER_MANAGER, QualityControlSignature::STATUS_SIGNED],
            )
            ->selectRaw('MAX(signed_at) AS last_signed_at')
            ->groupBy('quality_control_report_id');
    }

    private function qualityControlMakerSignatureExpression(): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "TRIM(COALESCE(json_extract(dashboard_qc.payload, '$.signature.signature_data'), ''))";
        }

        return "TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(dashboard_qc.payload, '$.signature.signature_data')), ''))";
    }

    private function monthLabel(int $month): string
    {
        return [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ][$month];
    }
}
