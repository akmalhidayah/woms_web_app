<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\OrderWorkshop;
use Illuminate\Database\Eloquent\Builder;
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
        $summary = $this->aggregate(clone $periodQuery);
        $reguRows = $this->reguSummary(clone $periodQuery);
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
        $years = $this->baseQuery()
            ->selectRaw("{$yearExpression} as dashboard_year")
            ->whereNotNull('orders.tanggal_order')
            ->distinct()
            ->pluck('dashboard_year')
            ->map(fn ($year): int => (int) $year)
            ->filter(fn (int $year): bool => $year > 0)
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
            ->leftJoin('workshop_handovers', 'workshop_handovers.order_id', '=', 'orders.id')
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
        $row = $query
            ->selectRaw('COUNT(orders.id) as total_order')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN workshop_handovers.id IS NOT NULL THEN 1 ELSE 0 END), 0) as completed_order',
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN order_workshops.progress_status IN (?, ?) AND workshop_handovers.id IS NULL THEN 1 ELSE 0 END), 0) as in_progress_order',
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
    private function reguSummary(Builder $query): array
    {
        $reguExpression = "TRIM(COALESCE(orders.catatan, ''))";
        $rows = $query
            ->selectRaw("{$reguExpression} as regu")
            ->selectRaw('COUNT(orders.id) as total_order')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN workshop_handovers.id IS NOT NULL THEN 1 ELSE 0 END), 0) as completed_order',
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN order_workshops.progress_status IN (?, ?) AND workshop_handovers.id IS NULL THEN 1 ELSE 0 END), 0) as in_progress_order',
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
            ->map(function (string $regu) use ($rows): array {
                $row = $rows->get($regu);

                return ['name' => $regu] + $this->metric(
                    (int) ($row?->getAttribute('total_order') ?? 0),
                    (int) ($row?->getAttribute('in_progress_order') ?? 0),
                    (int) ($row?->getAttribute('completed_order') ?? 0),
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

    /**
     * @return list<array{month: int, label: string, total: int, completed: int, percentage: float, target: int}>
     */
    private function completionTrend(int $year, ?int $selectedMonth): array
    {
        $lastMonth = $this->lastTrendMonth($year, $selectedMonth);
        $orderMonthExpression = $this->datePartExpression('month', 'orders.tanggal_order');
        $completionYearExpression = $this->datePartExpression('year', 'workshop_handovers.handed_over_at');
        $completionMonthExpression = $this->datePartExpression('month', 'workshop_handovers.handed_over_at');
        $trendEnd = Carbon::create($year, $lastMonth, 1)->endOfMonth();
        $monthlyOrders = $this->baseQuery()
            ->whereYear('orders.tanggal_order', $year)
            ->whereMonth('orders.tanggal_order', '<=', $lastMonth)
            ->selectRaw("{$orderMonthExpression} as order_month, COUNT(orders.id) as total_order")
            ->groupByRaw($orderMonthExpression)
            ->pluck('total_order', 'order_month')
            ->map(fn ($total): int => (int) $total);
        $completionGroups = $this->baseQuery()
            ->whereYear('orders.tanggal_order', $year)
            ->whereMonth('orders.tanggal_order', '<=', $lastMonth)
            ->whereNotNull('workshop_handovers.id')
            ->whereNotNull('workshop_handovers.handed_over_at')
            ->where('workshop_handovers.handed_over_at', '<=', $trendEnd)
            ->selectRaw("{$orderMonthExpression} as order_month")
            ->selectRaw("{$completionYearExpression} as completion_year")
            ->selectRaw("{$completionMonthExpression} as completion_month")
            ->selectRaw('COUNT(orders.id) as total_order')
            ->groupByRaw("{$orderMonthExpression}, {$completionYearExpression}, {$completionMonthExpression}")
            ->get();
        $cumulativeTotal = 0;

        return collect(range(1, $lastMonth))
            ->map(function (int $month) use ($year, $monthlyOrders, $completionGroups, &$cumulativeTotal): array {
                $cumulativeTotal += (int) $monthlyOrders->get($month, 0);
                $completed = $completionGroups->sum(function (Order $row) use ($year, $month): int {
                    $orderMonth = (int) $row->getAttribute('order_month');
                    $completionYear = (int) $row->getAttribute('completion_year');
                    $completionMonth = (int) $row->getAttribute('completion_month');
                    $completedByMonth = $completionYear < $year
                        || ($completionYear === $year && $completionMonth <= $month);

                    return $orderMonth <= $month && $completedByMonth
                        ? (int) $row->getAttribute('total_order')
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
        if ($month === 'all') {
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
