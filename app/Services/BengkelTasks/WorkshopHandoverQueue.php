<?php

declare(strict_types=1);

namespace App\Services\BengkelTasks;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\QualityControlSignature;
use App\Models\WorkshopHandover;
use App\Models\WorkshopWorkPackage;
use Illuminate\Database\Eloquent\Builder;

final class WorkshopHandoverQueue
{
    public function query(): Builder
    {
        return Order::query()
            ->with(['orderWorkshop', 'latestQualityControlReport.signatures', 'workshopHandover.recipient'])
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedWorkshop->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->whereDoesntHave('orderWorkshop', fn (Builder $workshop) => $workshop
                ->whereNotNull('legacy_completed_at'))
            ->where(function (Builder $query): void {
                $this->readyQuery($query);
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereDoesntHave('workshopHandover')
                    ->orWhereHas('workshopHandover', fn (Builder $handover) => $handover
                        ->where('status', WorkshopHandover::STATUS_WAITING_USER_SIGNATURE));
            });
    }

    public function readyQuery(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('workPackages', fn (Builder $package) => $package
                ->where('status', '!=', WorkshopWorkPackage::STATUS_COMPLETED))
            ->where(function (Builder $query): void {
                $query
                    ->where(function (Builder $critical): void {
                        $critical
                            ->whereHas('latestQualityControlReport', fn (Builder $report) => $report
                                ->where('status', 'submitted')
                                ->whereNotNull('payload->signature->signature_data')
                                ->where('payload->signature->signature_data', '!=', ''))
                            ->whereHas('latestQualityControlReport.signatures', fn (Builder $signature) => $signature
                                ->where('role_key', QualityControlSignature::ROLE_WORKSHOP_MANAGER)
                                ->where('status', QualityControlSignature::STATUS_SIGNED))
                            ->whereHas('latestQualityControlReport.signatures', fn (Builder $signature) => $signature
                                ->where('role_key', QualityControlSignature::ROLE_USER_MANAGER)
                                ->where('status', QualityControlSignature::STATUS_SIGNED))
                            ->whereHas('latestQualityControlReport.signatures', fn (Builder $signature) => $signature->whereIn('role_key', [
                                QualityControlSignature::ROLE_WORKSHOP_MANAGER,
                                QualityControlSignature::ROLE_USER_MANAGER,
                            ]), '=', 2)
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
        if ($order->relationLoaded('latestQualityControlReport')) {
            return $order->latestQualityControlReport !== null
                ? WorkshopHandover::PATH_CRITICAL
                : WorkshopHandover::PATH_NON_CRITICAL;
        }

        return $order->qualityControlReports()->exists()
            ? WorkshopHandover::PATH_CRITICAL
            : WorkshopHandover::PATH_NON_CRITICAL;
    }

    public function count(): int
    {
        return $this->query()->count();
    }

    public function isReady(Order $order): bool
    {
        $order->loadMissing(['orderWorkshop', 'latestQualityControlReport.signatures', 'workPackages']);

        if ($order->orderWorkshop?->legacyCompleted()) {
            return false;
        }

        if ($order->isWorkshopOrder() && $order->workPackages->isNotEmpty() && ! $order->allWorkPackagesCompleted()) {
            return false;
        }

        if ($order->latestQualityControlReport !== null) {
            return $order->latestQualityControlReport?->approvalCompleted() ?? false;
        }

        return $order->orderWorkshop?->progress_status === OrderWorkshop::PROGRESS_DONE;
    }
}
