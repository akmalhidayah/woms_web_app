<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\BudgetVerification;
use App\Models\Hpp;
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

        return Hpp::query()
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
            ->orderByDesc('amount')
            ->limit(10)
            ->get()
            ->map(fn (Hpp $hpp): array => [
                'section' => (string) $hpp->getAttribute('section'),
                'amount' => (int) round((float) $hpp->getAttribute('amount')),
            ])
            ->all();
    }
}
