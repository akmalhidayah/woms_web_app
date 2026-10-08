<?php

namespace App\Services\BengkelTasks;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\OrderWorkshop;

class WorkshopOrderSummaryService
{
    /**
     * @return array{total_workshop: int, total_service: int, processed_workshop: int, processed_service: int}
     */
    public function resolve(): array
    {
        $orders = Order::query()
            ->with([
                'orderWorkshop:id,order_id,preparation_status,progress_status',
                'purchaseOrder:id,order_id,progress_pekerjaan',
                'initialWork:id,order_id,progress_pekerjaan',
            ])
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedWorkshop->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
                OrderUserNoteStatus::ApprovedJasa->value,
            ])
            ->get(['id', 'prioritas', 'catatan_status']);

        $workshopOrders = $orders->filter(
            fn (Order $order): bool => in_array($order->catatan_status, [
                OrderUserNoteStatus::ApprovedWorkshop,
                OrderUserNoteStatus::ApprovedWorkshopJasa,
            ], true)
        );

        $serviceOrders = $orders->filter(
            fn (Order $order): bool => $order->catatan_status === OrderUserNoteStatus::ApprovedJasa
        );

        $completedWorkshop = $workshopOrders->filter(
            fn (Order $order): bool => $order->orderWorkshop?->progress_status === OrderWorkshop::PROGRESS_DONE
        )->count();

        $completedService = $serviceOrders->filter(function (Order $order): bool {
            $progress = Order::priorityPrimaryFor($order->prioritas) === 'emergency'
                ? $order->initialWork?->progress_pekerjaan
                : $order->purchaseOrder?->progress_pekerjaan;

            return (int) $progress >= 100;
        })->count();

        return [
            'total_workshop' => $workshopOrders->count(),
            'total_service' => $serviceOrders->count(),
            'processed_workshop' => max(0, $workshopOrders->count() - $completedWorkshop),
            'processed_service' => max(0, $serviceOrders->count() - $completedService),
        ];
    }
}
