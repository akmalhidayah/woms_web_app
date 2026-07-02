<?php

namespace App\Services\BengkelTasks;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;

class WorkshopOrderTaskSyncer
{
    public function syncOpenWorkshopOrders(): void
    {
        Order::query()
            ->with('orderWorkshop')
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedWorkshop->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    $this->syncOrder($order);
                }
            });
    }

    public function syncOrder(Order $order, ?OrderWorkshop $workshop = null): ?BengkelTask
    {
        $status = $order->catatan_status instanceof OrderUserNoteStatus
            ? $order->catatan_status->value
            : (string) $order->catatan_status;

        if (! in_array($status, [
            OrderUserNoteStatus::ApprovedWorkshop->value,
            OrderUserNoteStatus::ApprovedWorkshopJasa->value,
        ], true)) {
            return null;
        }

        $workshop ??= $order->orderWorkshop;
        $task = $this->resolveTask($order);
        $progressStatus = $workshop?->progress_status
            ?: $task->progress_status
            ?: OrderWorkshop::PROGRESS_MENUNGGU_JADWAL;

        $pendingReason = $progressStatus === OrderWorkshop::PROGRESS_PENDING
            ? ($workshop?->keterangan_progress ?: $task->pending_reason)
            : null;
        $jobName = mb_strtoupper(trim((string) $order->nama_pekerjaan));

        $task->forceFill([
            'order_id' => $order->id,
            'job_name' => $jobName !== '' ? $jobName : ($order->nomor_order ?: 'PEKERJAAN BENGKEL'),
            'notification_number' => $order->nomor_order ?: $order->notifikasi,
            'unit_work' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'usage_plan_date' => $order->target_selesai ?: $order->tanggal_order,
            'catatan' => $this->resolveRegu($order, $task),
            'progress_status' => $progressStatus,
            'is_completed' => $progressStatus === OrderWorkshop::PROGRESS_DONE,
            'pending_reason' => $pendingReason,
        ]);

        if (! $task->exists) {
            $task->person_in_charge = [];
            $task->person_in_charge_profiles = [];
        }

        if (! $task->exists || $task->isDirty()) {
            $task->save();
        }

        return $task;
    }

    private function resolveTask(Order $order): BengkelTask
    {
        $activeTask = BengkelTask::query()
            ->where('order_id', $order->id)
            ->whereNull('archived_at')
            ->latest('id')
            ->first();

        if ($activeTask) {
            return $activeTask;
        }

        $orderNumbers = collect([$order->nomor_order, $order->notifikasi])
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($orderNumbers !== []) {
            $manualTask = BengkelTask::query()
                ->whereNull('order_id')
                ->whereNull('archived_at')
                ->whereIn('notification_number', $orderNumbers)
                ->latest('id')
                ->first();

            if ($manualTask) {
                return $manualTask;
            }
        }

        return new BengkelTask();
    }

    private function resolveRegu(Order $order, BengkelTask $task): string
    {
        $regu = trim((string) $order->catatan);

        if (in_array($regu, [
            'Regu Fabrikasi',
            'Regu Bengkel (Refurbish)',
        ], true)) {
            return $regu;
        }

        $existingRegu = trim((string) $task->catatan);

        return $existingRegu !== '' ? $existingRegu : 'Regu Fabrikasi';
    }
}
