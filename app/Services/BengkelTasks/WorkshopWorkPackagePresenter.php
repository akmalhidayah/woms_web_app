<?php

namespace App\Services\BengkelTasks;

use App\Models\Order;

final class WorkshopWorkPackagePresenter
{
    /** @return list<array<string, mixed>> */
    public function snapshotForOrder(Order $order): array
    {
        $order->loadMissing('workPackages.assignments');

        return $order->workPackages
            ->sortBy('sequence')
            ->map(static fn ($package): array => [
                'id' => $package->id,
                'sequence' => $package->sequence,
                'display_no' => $package->display_no,
                'job_name' => $package->job_name,
                'description' => $package->description,
                'target_date' => $package->target_date?->format('Y-m-d'),
                'status' => $package->status,
                'completed_at' => $package->completed_at?->toISOString(),
                'assignments' => $package->assignments->map(static fn ($assignment): array => [
                    'pic_name' => $assignment->pic_name_snapshot,
                    'work_descriptions' => array_values((array) ($assignment->work_descriptions ?? [])),
                ])->values()->all(),
            ])->values()->all();
    }
}
