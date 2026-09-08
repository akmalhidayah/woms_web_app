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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class WorkshopDashboardService
{
    public const COMPLETION_TARGET = 90;

    private const SOURCE_WORKSHOP = 'workshop';

    private const SOURCE_OUTSOURCED = 'outsourced';

    /**
     * @return array<string, mixed>
     */
    public function resolve(?int $requestedYear = null, int|string|null $requestedMonth = null): array
    {
        $workloads = $this->workloadRecords();
        $availableYears = $this->availableYearsFrom($workloads);
        $currentYear = (int) Carbon::now()->year;
        $year = $requestedYear !== null && in_array($requestedYear, $availableYears, true)
            ? $requestedYear
            : $currentYear;
        $month = $this->normalizeMonth($requestedMonth);
        [$periodStart, $periodEnd] = $this->periodBounds($year, $month);
        $periodWorkloads = $this->workloadsForPeriod($workloads, $periodStart, $periodEnd);
        $summary = $this->aggregate($periodWorkloads, $periodStart, $periodEnd) + [
            'outsourced' => $periodWorkloads
                ->where('source', self::SOURCE_OUTSOURCED)
                ->count(),
        ];
        $reguRows = $this->reguSummary($periodWorkloads, $periodStart, $periodEnd);
        $trend = $this->completionTrend($workloads, $year, $month);

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
        return $this->availableYearsFrom($this->workloadRecords());
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
     * @return Collection<int, array<string, mixed>>
     */
    private function workloadRecords(): Collection
    {
        return $this->workshopWorkloads()
            ->concat($this->jobWaitingEstimatorWorkloads())
            ->unique('order_id')
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function workshopWorkloads(): Collection
    {
        $completedExpression = $this->completedExpression();
        $completionTimestampExpression = $this->completionTimestampExpression();

        return $this->baseQuery()
            ->whereNotNull('orders.tanggal_order')
            ->select([
                'orders.id as dashboard_order_id',
                'orders.tanggal_order as dashboard_entry_at',
                'orders.catatan as dashboard_regu',
                'orders.biaya as dashboard_cost',
                'order_workshops.started_at as dashboard_started_at',
            ])
            ->selectRaw("CASE WHEN {$completedExpression} THEN 1 ELSE 0 END as dashboard_completed")
            ->selectRaw("{$completionTimestampExpression} as dashboard_completed_at")
            ->get()
            ->map(function (Order $row): ?array {
                $entryAt = $this->asCarbon($row->getAttribute('dashboard_entry_at'));

                if (! $entryAt) {
                    return null;
                }

                return [
                    'order_id' => (int) $row->getAttribute('dashboard_order_id'),
                    'source' => self::SOURCE_WORKSHOP,
                    'regu' => trim((string) $row->getAttribute('dashboard_regu')),
                    'entry_at' => $entryAt,
                    'started_at' => $this->asCarbon($row->getAttribute('dashboard_started_at')),
                    'completed_at' => (bool) $row->getAttribute('dashboard_completed')
                        ? $this->asCarbon($row->getAttribute('dashboard_completed_at'))
                        : null,
                    'cost' => $this->moneyInt($row->getAttribute('dashboard_cost')),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function jobWaitingEstimatorWorkloads(): Collection
    {
        return PkmJobWaitingQuery::applyEntryEligibility(Order::query())
            ->where('orders.catatan_status', OrderUserNoteStatus::ApprovedJasa->value)
            ->with([
                'latestPurchaseOrder' => fn ($query) => $query->select([
                    'purchase_orders.id',
                    'purchase_orders.order_id',
                    'purchase_orders.approve_manager',
                    'purchase_orders.purchase_order_number',
                    'purchase_orders.progress_pekerjaan',
                    'purchase_orders.tanggal_mulai_pekerjaan',
                    'purchase_orders.tanggal_selesai_pekerjaan',
                    'purchase_orders.created_at',
                    'purchase_orders.updated_at',
                ]),
                'initialWork' => fn ($query) => $query->select([
                    'initial_works.id',
                    'initial_works.order_id',
                    'initial_works.tanggal_initial_work',
                    'initial_works.progress_pekerjaan',
                    'initial_works.tanggal_mulai_pekerjaan',
                    'initial_works.tanggal_selesai_pekerjaan',
                    'initial_works.created_at',
                ]),
            ])
            ->get(['orders.id', 'orders.prioritas'])
            ->map(function (Order $order): ?array {
                $purchaseOrder = $order->latestPurchaseOrder;
                $initialWork = $order->initialWork;
                $hasValidPurchaseOrder = $purchaseOrder !== null
                    && $purchaseOrder->approve_manager
                    && filled($purchaseOrder->purchase_order_number);
                $hasInitialWorkEligibility = in_array(
                    $order->prioritas,
                    [Order::PRIORITY_URGENT, Order::PRIORITY_HIGH],
                    true,
                ) && $initialWork !== null;
                $jobSource = $hasValidPurchaseOrder
                    ? $purchaseOrder
                    : ($hasInitialWorkEligibility ? $initialWork : null);

                if (! $jobSource) {
                    return null;
                }

                // There is no dedicated Job Waiting entry timestamp. Use the
                // source creation time because progress updates mutate updated_at.
                $entryAt = $this->earliestDate([
                    $hasValidPurchaseOrder
                        ? $this->asCarbon($purchaseOrder->created_at ?: $purchaseOrder->updated_at)
                        : null,
                    $hasInitialWorkEligibility
                        ? $this->asCarbon($initialWork->created_at ?: $initialWork->tanggal_initial_work)
                        : null,
                ]);

                if (! $entryAt) {
                    return null;
                }

                $progress = max(0, min(100, (int) $jobSource->progress_pekerjaan));

                return [
                    'order_id' => (int) $order->id,
                    'source' => self::SOURCE_OUTSOURCED,
                    'regu' => Order::WORKSHOP_REGU_ESTIMATOR,
                    'entry_at' => $entryAt,
                    'started_at' => $this->asCarbon($jobSource->tanggal_mulai_pekerjaan),
                    'completed_at' => $progress >= 100
                        ? $this->asCarbon($jobSource->tanggal_selesai_pekerjaan)
                        : null,
                    'cost' => 0,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $workloads
     * @return list<int>
     */
    private function availableYearsFrom(Collection $workloads): array
    {
        $currentYear = (int) Carbon::now()->year;
        $years = collect([$currentYear]);

        foreach ($workloads as $workload) {
            $entryYear = (int) $workload['entry_at']->year;
            $completionYear = $workload['completed_at']
                ? (int) $workload['completed_at']->year
                : max($entryYear, $currentYear);

            if ($entryYear <= $completionYear) {
                $years = $years->merge(range($entryYear, $completionYear));
            } else {
                $years->push($entryYear);
            }
        }

        return $years
            ->filter(fn (int $year): bool => $year > 0)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array{Carbon, Carbon}
     */
    private function periodBounds(int $year, ?int $month): array
    {
        $periodStart = Carbon::create($year, $month ?? 1, 1)->startOfDay();
        $periodEnd = $month !== null
            ? $periodStart->copy()->endOfMonth()
            : $periodStart->copy()->endOfYear();
        $now = Carbon::now();

        if ($year === (int) $now->year && $periodEnd->greaterThan($now)) {
            $periodEnd = $now->copy()->endOfDay();
        }

        return [$periodStart, $periodEnd];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $workloads
     * @return Collection<int, array<string, mixed>>
     */
    private function workloadsForPeriod(Collection $workloads, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        if ($periodStart->greaterThan($periodEnd)) {
            return collect();
        }

        return $workloads
            ->filter(function (array $workload) use ($periodStart, $periodEnd): bool {
                $completedAt = $workload['completed_at'];

                return $workload['entry_at']->lessThanOrEqualTo($periodEnd)
                    && ($completedAt === null || $completedAt->greaterThanOrEqualTo($periodStart));
            })
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $workloads
     * @return array<string, int|float|bool>
     */
    private function aggregate(Collection $workloads, Carbon $periodStart, Carbon $periodEnd): array
    {
        $completed = $workloads
            ->filter(fn (array $workload): bool => $this->completedWithin(
                $workload['completed_at'],
                $periodStart,
                $periodEnd,
            ))
            ->count();
        $inProgress = $workloads
            ->filter(fn (array $workload): bool => $this->inProgressAtEnd($workload, $periodEnd))
            ->count();
        $totalCost = $workloads
            ->filter(fn (array $workload): bool => $workload['source'] === self::SOURCE_WORKSHOP
                && $workload['entry_at']->greaterThanOrEqualTo($periodStart)
                && $workload['entry_at']->lessThanOrEqualTo($periodEnd))
            ->sum(fn (array $workload): int => (int) $workload['cost']);

        return $this->metric($workloads->count(), $inProgress, $completed, (int) $totalCost);
    }

    private function completedWithin(?Carbon $completedAt, Carbon $periodStart, Carbon $periodEnd): bool
    {
        return $completedAt !== null
            && $completedAt->greaterThanOrEqualTo($periodStart)
            && $completedAt->lessThanOrEqualTo($periodEnd);
    }

    /**
     * @param  array<string, mixed>  $workload
     */
    private function inProgressAtEnd(array $workload, Carbon $periodEnd): bool
    {
        $startedAt = $workload['started_at'];
        $completedAt = $workload['completed_at'];

        return $startedAt !== null
            && $startedAt->lessThanOrEqualTo($periodEnd)
            && ($completedAt === null || $completedAt->greaterThan($periodEnd));
    }

    /**
     * @param  list<?Carbon>  $dates
     */
    private function earliestDate(array $dates): ?Carbon
    {
        $earliest = collect($dates)
            ->filter(fn (?Carbon $date): bool => $date !== null)
            ->sortBy(fn (Carbon $date): int => $date->getTimestamp())
            ->first();

        return $earliest?->copy();
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof Carbon
            ? $value->copy()
            : Carbon::parse($value);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $workloads
     * @return array{items: list<array<string, int|float|string|bool>>, unknown_count: int}
     */
    private function reguSummary(Collection $workloads, Carbon $periodStart, Carbon $periodEnd): array
    {
        $officialRegu = Order::workshopReguOptions();
        $items = collect($officialRegu)
            ->map(function (string $regu) use ($workloads, $periodStart, $periodEnd): array {
                return ['name' => $regu] + $this->aggregate(
                    $workloads->where('regu', $regu)->values(),
                    $periodStart,
                    $periodEnd,
                );
            })
            ->values()
            ->all();
        $unknownCount = $workloads
            ->reject(fn (array $workload): bool => in_array($workload['regu'], $officialRegu, true))
            ->count();

        return [
            'items' => $items,
            'unknown_count' => $unknownCount,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $workloads
     * @return list<array<string, mixed>>
     */
    private function completionTrend(Collection $workloads, int $year, ?int $selectedMonth): array
    {
        $lastMonth = $this->lastTrendMonth($year, $selectedMonth);

        return collect(range(1, $lastMonth))
            ->map(function (int $month) use ($workloads, $year): array {
                [$periodStart, $periodEnd] = $this->periodBounds($year, $month);
                $monthlyWorkloads = $this->workloadsForPeriod($workloads, $periodStart, $periodEnd);
                $monthlySummary = $this->aggregate($monthlyWorkloads, $periodStart, $periodEnd);
                $regu = collect(Order::workshopReguOptions())
                    ->mapWithKeys(fn (string $regu): array => [
                        $regu => $this->aggregate(
                            $monthlyWorkloads->where('regu', $regu)->values(),
                            $periodStart,
                            $periodEnd,
                        ),
                    ])
                    ->all();

                return [
                    'month' => $month,
                    'year' => $year,
                    'label' => $this->monthLabel($month),
                    'period_label' => $this->monthFullLabel($month).' '.$year,
                    'total' => $monthlySummary['total'],
                    'completed' => $monthlySummary['completed'],
                    'incomplete' => $monthlySummary['incomplete'],
                    'percentage' => $monthlySummary['completion_percentage'],
                    'regu' => $regu,
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

    private function monthFullLabel(int $month): string
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$month];
    }
}
