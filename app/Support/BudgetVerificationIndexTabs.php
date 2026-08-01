<?php

namespace App\Support;

use App\Models\Hpp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BudgetVerificationIndexTabs
{
    public const TAB_ACTION = 'action';

    public const TAB_READY_PO = 'ready_po';

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
            self::TAB_READY_PO => 'Siap PO',
            self::TAB_HISTORY => 'Riwayat',
        ];
    }

    public function baseQuery(): Builder
    {
        return Hpp::query()->where('status', Hpp::STATUS_APPROVED);
    }

    public function apply(Builder $query, string $tab): Builder
    {
        return match ($this->normalize($tab)) {
            self::TAB_READY_PO => $this->applyReadyForPurchaseOrder($query),
            self::TAB_HISTORY => $this->applyHistory($query),
            default => $this->applyAction($query),
        };
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return [
            self::TAB_ACTION => $this->countFor(self::TAB_ACTION),
            self::TAB_READY_PO => $this->countFor(self::TAB_READY_PO),
            self::TAB_HISTORY => $this->countFor(self::TAB_HISTORY),
        ];
    }

    public function countFor(string $tab): int
    {
        return $this->apply($this->baseQuery(), $tab)->count();
    }

    public function applyLatestActivityOrder(Builder $query): Builder
    {
        $hppActivity = DB::table('hpps')->selectRaw('id AS hpp_id, updated_at AS activity_at');
        $verificationActivity = DB::table('budget_verifications')->selectRaw('hpp_id, updated_at AS activity_at');
        $purchaseOrderActivity = DB::table('purchase_orders')->selectRaw('hpp_id, updated_at AS activity_at');
        $allActivity = $hppActivity
            ->unionAll($verificationActivity)
            ->unionAll($purchaseOrderActivity);
        $latestActivity = DB::query()
            ->fromSub($allActivity, 'budget_verification_activity')
            ->selectRaw('hpp_id, MAX(activity_at) AS last_activity_at')
            ->groupBy('hpp_id');

        return $query
            ->leftJoinSub($latestActivity, 'latest_budget_activity', function ($join): void {
                $join->on('latest_budget_activity.hpp_id', '=', 'hpps.id');
            })
            ->select('hpps.*')
            ->orderByDesc('latest_budget_activity.last_activity_at')
            ->orderByDesc('hpps.id');
    }

    private function applyAction(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave(
                'budgetVerification',
                fn (Builder $verification): Builder => $verification->readyForPurchaseOrder()
            )
            ->whereDoesntHave('budgetVerification', fn (Builder $verification): Builder => $verification
                ->where('status_anggaran', 'Tidak Tersedia'))
            ->whereDoesntHave('purchaseOrder', fn (Builder $purchaseOrder): Builder => $purchaseOrder
                ->whereNotNull('purchase_order_number')
                ->whereRaw("TRIM(purchase_order_number) <> ''"));
    }

    private function applyReadyForPurchaseOrder(Builder $query): Builder
    {
        return $query
            ->whereHas(
                'budgetVerification',
                fn (Builder $verification): Builder => $verification->readyForPurchaseOrder()
            )
            ->where(function (Builder $purchaseOrder): void {
                $purchaseOrder
                    ->whereDoesntHave('purchaseOrder')
                    ->orWhereHas('purchaseOrder', function (Builder $existingPurchaseOrder): void {
                        $existingPurchaseOrder
                            ->whereNull('purchase_order_number')
                            ->orWhereRaw("TRIM(purchase_order_number) = ''");
                    });
            });
    }

    private function applyHistory(Builder $query): Builder
    {
        return $query->where(function (Builder $history): void {
            $history
                ->whereHas('budgetVerification', fn (Builder $verification): Builder => $verification
                    ->where('status_anggaran', 'Tidak Tersedia'))
                ->orWhereHas('purchaseOrder', fn (Builder $purchaseOrder): Builder => $purchaseOrder
                    ->whereNotNull('purchase_order_number')
                    ->whereRaw("TRIM(purchase_order_number) <> ''"));
        });
    }
}
