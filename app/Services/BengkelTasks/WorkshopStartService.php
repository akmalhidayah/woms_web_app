<?php

namespace App\Services\BengkelTasks;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\OrderWorkshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkshopStartService
{
    public function __construct(
        private readonly WorkshopOrderTaskSyncer $workshopOrderTaskSyncer,
    ) {}

    /**
     * @return array{workshop: OrderWorkshop, started: bool}
     */
    public function start(Order $order): array
    {
        return DB::transaction(function () use ($order): array {
            $status = $order->catatan_status instanceof OrderUserNoteStatus
                ? $order->catatan_status->value
                : (string) $order->catatan_status;

            if (! in_array($status, [
                OrderUserNoteStatus::ApprovedWorkshop->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ], true)) {
                throw ValidationException::withMessages([
                    'progress_status' => 'Order ini tidak termasuk Order Pekerjaan Bengkel.',
                ]);
            }

            $workshop = OrderWorkshop::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if ($workshop?->started_at !== null) {
                return ['workshop' => $workshop, 'started' => false];
            }

            $taskProgress = null;

            if (! $workshop || blank($workshop->progress_status)) {
                $taskProgress = $order->bengkelTasks()
                    ->whereNull('archived_at')
                    ->latest('id')
                    ->value('progress_status');
            }

            $currentProgress = $workshop?->progress_status
                ?: $taskProgress
                ?: OrderWorkshop::PROGRESS_MENUNGGU_JADWAL;

            if ($currentProgress !== OrderWorkshop::PROGRESS_MENUNGGU_JADWAL) {
                throw ValidationException::withMessages([
                    'progress_status' => 'Pekerjaan legacy sudah berjalan, tetapi waktu mulai belum tercatat.',
                ]);
            }

            if (! $workshop) {
                $workshop = $this->workshopOrderTaskSyncer->ensureWorkshopLifecycle($order);
                $workshop = $workshop
                    ? OrderWorkshop::query()->whereKey($workshop->id)->lockForUpdate()->first()
                    : null;

                if (! $workshop) {
                    throw ValidationException::withMessages([
                        'progress_status' => 'Data Order Pekerjaan Bengkel tidak dapat disiapkan.',
                    ]);
                }
            }

            $workshop->forceFill([
                'started_at' => now(),
                'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
            ])->save();

            $this->workshopOrderTaskSyncer->syncProgress($order, $workshop);

            return ['workshop' => $workshop->fresh(), 'started' => true];
        });
    }

    public function assertProgressTransitionAllowed(?OrderWorkshop $workshop, string $requestedProgress): void
    {
        if (! $workshop) {
            return;
        }

        $currentProgress = $workshop->progress_status ?: OrderWorkshop::PROGRESS_MENUNGGU_JADWAL;

        if ($currentProgress === OrderWorkshop::PROGRESS_MENUNGGU_JADWAL
            && $workshop->started_at === null
            && $requestedProgress !== OrderWorkshop::PROGRESS_MENUNGGU_JADWAL) {
            throw ValidationException::withMessages([
                'progress_status' => 'Klik Start Pekerjaan sebelum mengubah Progress Pekerjaan.',
            ]);
        }

        if ($currentProgress !== OrderWorkshop::PROGRESS_MENUNGGU_JADWAL
            && $requestedProgress === OrderWorkshop::PROGRESS_MENUNGGU_JADWAL) {
            throw ValidationException::withMessages([
                'progress_status' => 'Progress pekerjaan yang sudah berjalan tidak dapat dikembalikan ke Menunggu Jadwal.',
            ]);
        }
    }
}
