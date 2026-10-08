<?php

declare(strict_types=1);

namespace App\Services\BengkelTasks;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelPic;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\WorkshopWorkPackage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
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
            throw (new ModelNotFoundException)->setModel(Order::class, [$order->getKey()]);
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

    public function assertOrderPackagesMutable(Order $order): void
    {
        $this->assertWorkshopOrder($order);
        $order->loadMissing(['orderWorkshop', 'qualityControlReports', 'workshopHandover', 'bengkelTasks']);

        $archived = $order->bengkelTasks->contains(fn ($task): bool => $task->archived_at !== null);
        if (in_array($order->orderWorkshop?->progress_status, [
            OrderWorkshop::PROGRESS_QUALITY_CONTROL,
            OrderWorkshop::PROGRESS_DONE,
        ], true) || $order->qualityControlReports->isNotEmpty() || $order->workshopHandover !== null || $archived) {
            throw ValidationException::withMessages([
                'work_package' => 'Pembagian pekerjaan tidak dapat diubah karena proses Quality Control atau Serah Terima telah dimulai.',
            ]);
        }
    }

    public function assertPackageMutable(WorkshopWorkPackage $package): void
    {
        $package->loadMissing(['order.orderWorkshop', 'order.qualityControlReports', 'order.workshopHandover', 'order.bengkelTasks']);

        if (! $package->order instanceof Order) {
            throw (new ModelNotFoundException)->setModel(WorkshopWorkPackage::class, [$package->getKey()]);
        }

        $this->assertWorkshopOrder($package->order);

        if ($package->isLocked()) {
            throw ValidationException::withMessages([
                'work_package' => 'Pembagian pekerjaan sudah terkunci karena proses lanjutan telah dimulai.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Order $order, array $data, ?int $userId = null): WorkshopWorkPackage
    {
        return $this->createBatch($order, [$data], $userId)->first();
    }

    /** @param array<int, array<string, mixed>> $rows */
    public function createBatch(Order $order, array $rows, ?int $userId = null): Collection
    {
        $this->assertOrderPackagesMutable($order);

        try {
            return DB::transaction(function () use ($order, $rows, $userId) {
                $workshop = $order->orderWorkshop()->lockForUpdate()->first();
                if ($workshop === null) {
                    $this->assertOrderPackagesMutable($order);
                }
                $lockedOrder = $order->fresh(['orderWorkshop', 'qualityControlReports', 'workshopHandover', 'bengkelTasks']) ?: $order;
                $this->assertOrderPackagesMutable($lockedOrder);
                $existingCount = WorkshopWorkPackage::query()->where('order_id', $order->getKey())->count();
                if ($existingCount + count($rows) > 99) {
                    throw ValidationException::withMessages(['packages' => 'Jumlah paket dalam satu order tidak boleh melebihi 99.']);
                }

                $sequence = (int) WorkshopWorkPackage::query()->where('order_id', $order->getKey())->max('sequence');
                $created = collect();
                foreach ($rows as $data) {
                    $sequence++;
                    $package = WorkshopWorkPackage::create([
                        'order_id' => $order->getKey(),
                        'sequence' => $sequence,
                        'display_no' => $this->displayNumber($order, $sequence),
                        'job_name' => trim((string) ($data['job_name'] ?? '')),
                        'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
                        'target_date' => $data['target_date'] ?? $order->target_selesai,
                        'status' => WorkshopWorkPackage::STATUS_NOT_STARTED,
                        'pending_reason' => null,
                        'completed_at' => null,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                    $this->syncAssignments($package, $data['assignments'] ?? []);
                    $created->push($package->load('assignments'));
                }

                return $created;
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'packages' => 'Paket pekerjaan sudah dibuat oleh proses lain. Muat ulang halaman lalu coba lagi.',
                ]);
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WorkshopWorkPackage $package, array $data, ?int $userId = null): WorkshopWorkPackage
    {
        $this->assertPackageMutable($package);

        return DB::transaction(function () use ($package, $data, $userId): WorkshopWorkPackage {
            $locked = $this->lockPackageParent($package);
            $this->assertPackageMutable($locked);
            $locked->fill([
                'job_name' => trim((string) ($data['job_name'] ?? $locked->job_name)),
                'description' => array_key_exists('description', $data) && filled($data['description'])
                    ? trim((string) $data['description']) : (array_key_exists('description', $data) ? null : $locked->description),
                'target_date' => $data['target_date'] ?? $locked->target_date,
                'updated_by' => $userId,
            ]);
            $locked->save();

            if (array_key_exists('assignments', $data)) {
                $this->syncAssignments($locked, $data['assignments']);
            } elseif ($locked->status !== WorkshopWorkPackage::STATUS_NOT_STARTED && ! $locked->hasAssignments()) {
                throw ValidationException::withMessages(['assignments' => 'PIC wajib diisi sebelum paket dikerjakan.']);
            }

            return $locked->load('assignments');
        });
    }

    public function updateStatus(WorkshopWorkPackage $package, string $status, ?string $pendingReason = null, ?int $userId = null): WorkshopWorkPackage
    {
        if (! array_key_exists($status, WorkshopWorkPackage::statusOptions())) {
            throw ValidationException::withMessages(['status' => 'Status paket tidak valid.']);
        }

        $this->assertPackageMutable($package);

        return DB::transaction(function () use ($package, $status, $pendingReason, $userId): WorkshopWorkPackage {
            $locked = $this->lockPackageParent($package)->load('assignments');
            $this->assertPackageMutable($locked);
            if ($status !== WorkshopWorkPackage::STATUS_NOT_STARTED && ! $this->assignmentsComplete($locked)) {
                throw ValidationException::withMessages(['status' => 'PIC dan uraian pekerjaan wajib diisi sebelum status paket dilanjutkan.']);
            }
            if ($status === WorkshopWorkPackage::STATUS_PENDING && blank($pendingReason)) {
                throw ValidationException::withMessages(['pending_reason' => 'Alasan pending wajib diisi.']);
            }
            $wasCompleted = $locked->status === WorkshopWorkPackage::STATUS_COMPLETED;
            $locked->status = $status;
            $locked->pending_reason = $status === WorkshopWorkPackage::STATUS_PENDING ? trim((string) $pendingReason) : null;
            $locked->completed_at = $status === WorkshopWorkPackage::STATUS_COMPLETED
                ? ($wasCompleted && $locked->completed_at ? $locked->completed_at : now())
                : null;
            $locked->updated_by = $userId;
            $locked->save();

            return $locked;
        });
    }

    public function delete(WorkshopWorkPackage $package): void
    {
        $this->assertPackageMutable($package);
        DB::transaction(function () use ($package): void {
            $locked = $this->lockPackageParent($package);
            $this->assertPackageMutable($locked);
            $locked->delete();
        });
    }

    private function displayNumber(Order $order, int $sequence): string
    {
        return $order->nomor_order.'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
    }

    private function syncAssignments(WorkshopWorkPackage $package, mixed $assignments): void
    {
        $rows = is_array($assignments) ? array_values($assignments) : [];
        $seen = [];
        $normalized = [];

        foreach ($rows as $index => $assignment) {
            $picId = (int) ($assignment['pic_id'] ?? $assignment['bengkel_pic_id'] ?? 0);
            if ($picId < 1 && blank(implode('', array_map('strval', (array) ($assignment['descriptions'] ?? $assignment['work_descriptions'] ?? []))))) {
                continue;
            }
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

    private function assignmentsComplete(WorkshopWorkPackage $package): bool
    {
        return $package->assignments->isNotEmpty() && $package->assignments->every(static function ($assignment): bool {
            return (int) $assignment->bengkel_pic_id > 0
                && filled($assignment->pic_name_snapshot)
                && collect($assignment->work_descriptions ?? [])->filter(static fn ($value): bool => filled($value))->isNotEmpty();
        });
    }

    private function lockPackageParent(WorkshopWorkPackage $package): WorkshopWorkPackage
    {
        $orderId = (int) $package->order_id;
        $order = Order::query()->findOrFail($orderId);
        $workshop = $order->orderWorkshop()->lockForUpdate()->first();
        if ($workshop === null) {
            $this->assertWorkshopOrder($order);
        }
        $locked = WorkshopWorkPackage::query()->findOrFail($package->getKey());
        $locked->setRelation('order', $order->fresh(['orderWorkshop', 'qualityControlReports', 'workshopHandover', 'bengkelTasks']));

        return $locked;
    }
}
