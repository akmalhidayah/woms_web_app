<?php

namespace App\Support;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

final class PkmJobWaitingQuery
{
    public static function query(): Builder
    {
        return self::apply(Order::query());
    }

    public static function apply(Builder $query): Builder
    {
        return self::applyEntryEligibility($query)
            ->whereDoesntHave('lhppBasts', function (Builder $bastQuery): void {
                $bastQuery
                    ->where('termin_type', 'termin_1')
                    ->where(function (Builder $approvalQuery): void {
                        $approvalQuery
                            ->approvalStarted()
                            ->orWhere('quality_control_status', 'approved');
                    });
            });
    }

    public static function applyEntryEligibility(Builder $query): Builder
    {
        return $query
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('purchaseOrder', function (Builder $purchaseOrderQuery): void {
                        $purchaseOrderQuery
                            ->where('approve_manager', true)
                            ->whereNotNull('purchase_order_number')
                            ->whereRaw("TRIM(purchase_order_number) <> ''");
                    })
                    ->orWhere(function (Builder $emergencyQuery): void {
                        $emergencyQuery
                            ->whereIn('prioritas', [
                                Order::PRIORITY_URGENT,
                                Order::PRIORITY_HIGH,
                            ])
                            ->has('initialWork');
                    });
            });
    }
}
