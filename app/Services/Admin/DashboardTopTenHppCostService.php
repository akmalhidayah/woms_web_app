<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementMonthlyRealization;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class DashboardTopTenHppCostService
{
    /**
     * @return list<array{section: string, amount: int}>
     */
    public function resolve(
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        ?string $costCategory = null,
    ): array
    {
        if ($costCategory !== null && ! array_key_exists($costCategory, BudgetVerification::kategoriBiayaOptions())) {
            return [];
        }

        $systemRows = Hpp::query()
            ->join('orders', 'orders.id', '=', 'hpps.order_id')
            ->whereNotNull('hpps.submitted_at')
            ->whereIn('hpps.status', [
                Hpp::STATUS_IN_REVIEW,
                Hpp::STATUS_APPROVED,
                Hpp::STATUS_REJECTED,
            ])
            ->whereBetween('hpps.submitted_at', [$periodStart, $periodEnd])
            ->whereRaw("TRIM(COALESCE(orders.seksi, '')) <> ''")
            ->when($costCategory !== null, fn (Builder $query): Builder => $query
                ->whereHas('budgetVerification', fn (Builder $budgetQuery): Builder => $budgetQuery
                    ->where('kategori_biaya', $costCategory)))
            ->whereNotExists(function ($query) use ($periodStart, $periodEnd): void {
                $query
                    ->selectRaw('1')
                    ->from('hpps as newer_hpps')
                    ->whereColumn('newer_hpps.order_id', 'hpps.order_id')
                    ->whereNotNull('newer_hpps.submitted_at')
                    ->whereIn('newer_hpps.status', [
                        Hpp::STATUS_IN_REVIEW,
                        Hpp::STATUS_APPROVED,
                        Hpp::STATUS_REJECTED,
                    ])
                    ->whereBetween('newer_hpps.submitted_at', [$periodStart, $periodEnd])
                    ->where(function ($newerQuery): void {
                        $newerQuery
                            ->whereColumn('newer_hpps.submitted_at', '>', 'hpps.submitted_at')
                            ->orWhere(function ($tieQuery): void {
                                $tieQuery
                                    ->whereColumn('newer_hpps.submitted_at', 'hpps.submitted_at')
                                    ->whereColumn('newer_hpps.id', '>', 'hpps.id');
                            });
                    });
            })
            ->selectRaw('TRIM(orders.seksi) as section, SUM(hpps.total_keseluruhan) as amount')
            ->groupByRaw('TRIM(orders.seksi)')
            ->get()
            ->map(fn (Hpp $hpp): array => [
                'section' => (string) $hpp->getAttribute('section'),
                'amount' => (int) $hpp->getAttribute('amount'),
            ])
            ->all();

        $startPeriod = ((int) $periodStart->format('Y') * 100) + (int) $periodStart->format('n');
        $endPeriod = ((int) $periodEnd->format('Y') * 100) + (int) $periodEnd->format('n');
        $manualRows = OutlineAgreementMonthlyRealization::query()
            ->whereHas('outlineAgreement', fn (Builder $query): Builder => $query
                ->where('status', OutlineAgreement::STATUS_ACTIVE))
            ->whereRaw("TRIM(COALESCE(seksi, '')) <> ''")
            ->whereRaw('((year * 100) + month) BETWEEN ? AND ?', [$startPeriod, $endPeriod])
            ->when($costCategory !== null, fn (Builder $query): Builder => $query
                ->where('kategori_biaya', $costCategory))
            ->selectRaw('TRIM(seksi) as section, SUM(amount) as amount')
            ->groupByRaw('TRIM(seksi)')
            ->get()
            ->map(fn (OutlineAgreementMonthlyRealization $realization): array => [
                'section' => (string) $realization->getAttribute('section'),
                'amount' => (int) $realization->getAttribute('amount'),
            ])
            ->all();

        $merged = [];

        foreach ([...$systemRows, ...$manualRows] as $row) {
            $section = trim($row['section']);

            if ($section === '') {
                continue;
            }

            $merged[$section] = ($merged[$section] ?? 0) + $row['amount'];
        }

        uksort($merged, function (string $left, string $right) use ($merged): int {
            $amountComparison = $merged[$right] <=> $merged[$left];

            return $amountComparison !== 0 ? $amountComparison : strcasecmp($left, $right);
        });

        return collect($merged)
            ->take(10)
            ->map(fn (int $amount, string $section): array => [
                'section' => $section,
                'amount' => $amount,
            ])
            ->values()
            ->all();
    }
}
