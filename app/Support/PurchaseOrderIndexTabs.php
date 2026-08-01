<?php

namespace App\Support;

use App\Models\Hpp;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderIndexTabs
{
    public const TAB_ACTION = 'action';

    public const TAB_READY = 'ready';

    public const TAB_IN_PROGRESS = 'in_progress';

    public const TAB_HISTORY = 'history';

    public function normalize(?string $tab): string
    {
        return array_key_exists((string) $tab, $this->options())
            ? (string) $tab
            : self::TAB_ACTION;
    }

    /** @return array<string, string> */
    public function options(): array
    {
        return [
            self::TAB_ACTION => 'Perlu Tindakan',
            self::TAB_READY => 'Siap Dikerjakan',
            self::TAB_IN_PROGRESS => 'Dalam Proses',
            self::TAB_HISTORY => 'Riwayat',
        ];
    }

    public function baseQuery(): Builder
    {
        return Hpp::query()
            ->whereHas(
                'budgetVerification',
                fn (Builder $query): Builder => $query->readyForPurchaseOrder()
            );
    }

    public function apply(Builder $query, string $tab): Builder
    {
        return match ($this->normalize($tab)) {
            self::TAB_READY => $this->applyActivePurchaseOrder($query)
                ->whereHas('purchaseOrder', fn (Builder $purchaseOrder): Builder => $purchaseOrder
                    ->where(function (Builder $progress): void {
                        $progress
                            ->whereNull('progress_pekerjaan')
                            ->orWhere('progress_pekerjaan', 0);
                    })),
            self::TAB_IN_PROGRESS => $this->applyActivePurchaseOrder($query)
                ->whereHas('purchaseOrder', fn (Builder $purchaseOrder): Builder => $purchaseOrder
                    ->whereBetween('progress_pekerjaan', [1, 99])),
            self::TAB_HISTORY => $this->applyActivePurchaseOrder($query)
                ->whereHas('purchaseOrder', fn (Builder $purchaseOrder): Builder => $purchaseOrder
                    ->where('progress_pekerjaan', '>=', 100)),
            default => $this->applyAction($query),
        };
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return [
            self::TAB_ACTION => $this->countFor(self::TAB_ACTION),
            self::TAB_READY => $this->countFor(self::TAB_READY),
            self::TAB_IN_PROGRESS => $this->countFor(self::TAB_IN_PROGRESS),
            self::TAB_HISTORY => $this->countFor(self::TAB_HISTORY),
        ];
    }

    public function countFor(string $tab): int
    {
        return $this->apply($this->baseQuery(), $tab)->count();
    }

    public function applyLatestActivityOrder(Builder $query): Builder
    {
        return $query
            ->leftJoin(
                'purchase_orders as po_activity',
                'po_activity.hpp_id',
                '=',
                'hpps.id'
            )
            ->select('hpps.*')
            ->orderByRaw('COALESCE(po_activity.updated_at, hpps.updated_at) DESC')
            ->orderByDesc('hpps.id');
    }

    private function applyAction(Builder $query): Builder
    {
        return $query->where(function (Builder $action): void {
            $action
                ->whereDoesntHave('purchaseOrder')
                ->orWhereHas('purchaseOrder', function (Builder $purchaseOrder): void {
                    $purchaseOrder
                        ->whereNull('purchase_order_number')
                        ->orWhereRaw("TRIM(purchase_order_number) = ''")
                        ->orWhere(function (Builder $managerApproval): void {
                            $managerApproval
                                ->whereNull('approve_manager')
                                ->orWhere('approve_manager', false);
                        });
                });
        });
    }

    private function applyActivePurchaseOrder(Builder $query): Builder
    {
        return $query->whereHas('purchaseOrder', function (Builder $purchaseOrder): void {
            $purchaseOrder
                ->whereNotNull('purchase_order_number')
                ->whereRaw("TRIM(purchase_order_number) <> ''")
                ->where('approve_manager', true);
        });
    }
}
