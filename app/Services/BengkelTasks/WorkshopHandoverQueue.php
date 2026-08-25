<?php

declare(strict_types=1);

namespace App\Services\BengkelTasks;

use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\QualityControlSignature;
use Illuminate\Database\Eloquent\Builder;

final class WorkshopHandoverQueue
{
    public function query(): Builder
    {
        return Order::query()
            ->with(['orderWorkshop', 'latestQualityControlReport.signatures'])
            ->where(function (Builder $query): void {
                $query
                    ->where(function (Builder $critical): void {
                        $critical
                            ->whereHas('latestQualityControlReport.signatures')
                            ->whereDoesntHave('latestQualityControlReport.signatures', fn (Builder $signature) => $signature
                                ->where('status', '!=', QualityControlSignature::STATUS_SIGNED));
                    })
                    ->orWhere(function (Builder $nonCritical): void {
                        $nonCritical
                            ->whereHas('orderWorkshop', fn (Builder $workshop) => $workshop
                                ->where('progress_status', OrderWorkshop::PROGRESS_DONE))
                            ->whereDoesntHave('qualityControlReports');
                    });
            });
    }

    public function path(Order $order): string
    {
        return $order->qualityControlReports()->exists() ? 'Critical' : 'Non-Critical';
    }

    public function count(): int
    {
        return $this->query()->count();
    }
}
