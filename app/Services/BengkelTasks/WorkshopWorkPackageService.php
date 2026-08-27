<?php

declare(strict_types=1);

namespace App\Services\BengkelTasks;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelPic;
use App\Models\Order;
use App\Models\WorkshopWorkPackage;
use App\Models\WorkshopWorkPackageAssignment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WorkshopWorkPackageService
{
    /** @var list<string> */
    private const WORKSHOP_STATUSES = [
        OrderUserNoteStatus::ApprovedWorkshop->value,
        OrderUserNoteStatus::ApprovedWorkshopJasa->value,
    ];

    public function assertWorkshopOrder(Order $order): void
    {
        $order->loadMissing('orderWorkshop');

        if ($order->orderWorkshop === null || ! in_array($order->catatan_status?->value, self::WORKSHOP_STATUSES, true)) {
            throw (new ModelNotFoundException())->setModel(Order::class, [$order->getKey()]);
        }
    }

    public function assertParentMayAdvance(Order $order): void
    {
        $this->assertWorkshopOrder($order);
        $order->loadMissing('workPackages');

        // A normal workshop order keeps its existing flow. This guard is only
        // meaningful once the parent actually has work packages.
        if ($order->workPackages->isNotEmpty() && ! $order->allWorkPackagesCompleted()) {
            $remaining = $order->workPackages->where('status', '!=', WorkshopWorkPackage::STATUS_COMPLETED)->count();
            throw ValidationException::withMessages([
                'progress_status' => "Order belum dapat dilanjutkan karena {$remaining} paket pekerjaan belum selesai.",
            ]);
        }
    }

    public function assertPackageMutable(WorkshopWorkPackage $package): void
    {
        $package->loadMissing(['order.orderWorkshop', 'order.qualityControlReports', 'order.workshopHandover', 'order.bengkelTasks']);

        if (! $package->order instanceof Order) {
            throw (new ModelNotFoundException())->setModel(WorkshopWorkPackage::class, [$package->getKey()]);
        }

        $this->assertWorkshopOrder($package->order);

        if ($package->isLocked()) {
            throw ValidationException::withMessages([
                'work_package' => 'Pembagian pekerjaan sudah terkunci karena proses lanjutan telah dimulai.',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(Order $order, array $data, ?int $userId = null): WorkshopWorkPackage
    {
        $this->assertWorkshopOrder($order);

        return DB::transaction(function () use ($order, $data, $userId): WorkshopWorkPackage {
            $workshop = $order->orderWorkshop()->lockForUpdate()->first();
            if ($workshop === null) {
                $this->assertWorkshopOrder($order);
            }

            if (WorkshopWorkPackage::query()->where('order_id', $order->getKey())->count() >= 99) {
                throw ValidationException::withMessages(['job_name' => 'Satu Order maksimal memiliki 99 paket pekerjaan.']);
            }

            $sequence = (int) (WorkshopWorkPackage::query()
                ->where('order_id', $order->getKey())
                ->lockForUpdate()
                ->max('sequence')) + 1;

            $package = WorkshopWorkPackage::create([
                'order_id' => $order->getKey(),
                'sequence' => $sequence,
                'display_no' => $this->displayNumber($order, $sequence),
                'job_name' => trim((string) $data['job_name']),
                'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
                'target_date' => $data['target_date'] ?? $order->target_selesai,
                'status' => $data['status'] ?? WorkshopWorkPackage::STATUS_NOT_STARTED,
                'pending_reason' => ($data['status'] ?? null) === WorkshopWorkPackage::STATUS_PENDING
                    ? ($data['pending_reason'] ?? null)
                    : null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if (($data['status'] ?? WorkshopWorkPackage::STATUS_NOT_STARTED) === WorkshopWorkPackage::STATUS_PENDING
                && blank($data['pending_reason'] ?? null)) {
                throw ValidationException::withMessages(['pending_reason' => 'Alasan pending wajib diisi.']);
            }

            $this->syncAssignments($package, $data['assignments'] ?? []);

            return $package->load('assignments');
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(WorkshopWorkPackage $package, array $data, ?int $userId = null): WorkshopWorkPackage
    {
        $this->assertPackageMutable($package);

        return DB::transaction(function () use ($package, $data, $userId): WorkshopWorkPackage {
            $locked = WorkshopWorkPackage::query()->lockForUpdate()->findOrFail($package->getKey());
            $this->assertPackageMutable($locked);
            $status = $data['status'] ?? $locked->status;

            $locked->fill([
                'job_name' => trim((string) ($data['job_name'] ?? $locked->job_name)),
                'description' => array_key_exists('description', $data) && filled($data['description'])
                    ? trim((string) $data['description']) : (array_key_exists('description', $data) ? null : $locked->description),
                'target_date' => $data['target_date'] ?? $locked->target_date,
                'status' => $status,
                'pending_reason' => $status === WorkshopWorkPackage::STATUS_PENDING ? ($data['pending_reason'] ?? null) : null,
                'completed_at' => $status === WorkshopWorkPackage::STATUS_COMPLETED
                    ? ($locked->status === WorkshopWorkPackage::STATUS_COMPLETED ? $locked->completed_at : now())
                    : null,
                'updated_by' => $userId,
            ]);
            $locked->save();

            if (array_key_exists('assignments', $data)) {
                $this->syncAssignments($locked, $data['assignments']);
            } elseif ($locked->status !== WorkshopWorkPackage::STATUS_NOT_STARTED && ! $locked->hasAssignments()) {
                throw ValidationException::withMessages(['assignments' => 'PIC wajib diisi sebelum paket dikerjakan.']);
            }

            if ($status === WorkshopWorkPackage::STATUS_PENDING && blank($data['pending_reason'] ?? null) && blank($locked->pending_reason)) {
                throw ValidationException::withMessages(['pending_reason' => 'Alasan pending wajib diisi.']);
            }

            return $locked->load('assignments');
        });
    }

    public function updateStatus(WorkshopWorkPackage $package, string $status, ?string $pendingReason = null, ?int $userId = null): WorkshopWorkPackage
    {
        if (! array_key_exists($status, WorkshopWorkPackage::statusOptions())) {
            throw ValidationException::withMessages(['status' => 'Status paket tidak valid.']);
        }

        return $this->update($package, [
            'status' => $status,
            'pending_reason' => $pendingReason,
        ], $userId);
    }

    public function delete(WorkshopWorkPackage $package): void
    {
        $this->assertPackageMutable($package);
        DB::transaction(fn (): bool => (bool) $package->delete());
    }

    private function displayNumber(Order $order, int $sequence): string
    {
        return $order->nomor_order.'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
    }

    /** @param mixed $assignments */
    private function syncAssignments(WorkshopWorkPackage $package, mixed $assignments): void
    {
        $rows = is_array($assignments) ? array_values($assignments) : [];
        $seen = [];
        $normalized = [];

        foreach ($rows as $index => $assignment) {
            $picId = (int) ($assignment['pic_id'] ?? $assignment['bengkel_pic_id'] ?? 0);
            if ($picId < 1 || isset($seen[$picId])) {
                throw ValidationException::withMessages(['assignments' => 'PIC paket wajib unik dan valid.']);
            }

            $pic = BengkelPic::query()->find($picId);
            if (! $pic) {
                throw ValidationException::withMessages(['assignments' => 'PIC yang dipilih tidak ditemukan.']);
            }

            $descriptions = collect($assignment['descriptions'] ?? $assignment['work_descriptions'] ?? [])
                ->map(static fn ($value): string => trim((string) $value))
                ->filter()
                ->values()
                ->all();
            if ($descriptions === []) {
                throw ValidationException::withMessages(['assignments' => 'Setiap PIC wajib memiliki minimal satu uraian pekerjaan.']);
            }

            $seen[$picId] = true;
            $normalized[] = [
                'bengkel_pic_id' => $pic->getKey(),
                'pic_name_snapshot' => $pic->name,
                'pic_avatar_path_snapshot' => $pic->avatar_path,
                'avatar_position_x' => $pic->avatar_position_x,
                'avatar_position_y' => $pic->avatar_position_y,
                'work_descriptions' => $descriptions,
                'sort_order' => $index,
            ];
        }

        if ($package->status !== WorkshopWorkPackage::STATUS_NOT_STARTED && $normalized === []) {
            throw ValidationException::withMessages(['assignments' => 'PIC wajib diisi sebelum paket dikerjakan.']);
        }

        $package->assignments()->delete();
        foreach ($normalized as $row) {
            $package->assignments()->create($row);
        }
    }
}
