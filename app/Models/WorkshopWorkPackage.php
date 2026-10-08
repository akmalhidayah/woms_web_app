<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkshopWorkPackage extends Model
{
    use HasFactory;

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'order_id', 'sequence', 'display_no', 'job_name', 'description', 'target_date',
        'status', 'pending_reason', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_NOT_STARTED => 'Belum Mulai',
            self::STATUS_IN_PROGRESS => 'Dikerjakan',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Selesai Paket',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkshopWorkPackageAssignment::class, 'work_package_id')->orderBy('sort_order');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? 'Belum Mulai';
    }

    public function displayNumber(): string
    {
        return (string) $this->display_no;
    }

    public function hasAssignments(): bool
    {
        return $this->relationLoaded('assignments')
            ? $this->assignments->isNotEmpty()
            : $this->assignments()->exists();
    }

    public function isLocked(): bool
    {
        $order = $this->relationLoaded('order') ? $this->order : $this->order()->with([
            'orderWorkshop', 'qualityControlReports', 'workshopHandover', 'bengkelTasks',
        ])->first();

        if ($order !== null && ! $order->relationLoaded('bengkelTasks')) {
            $order->load('bengkelTasks');
        }

        $archivedParentTask = $order?->relationLoaded('bengkelTasks')
            && $order->bengkelTasks->contains(fn (BengkelTask $task): bool => $task->archived_at !== null);

        return $order === null
            || in_array($order->orderWorkshop?->progress_status, [
                OrderWorkshop::PROGRESS_QUALITY_CONTROL,
                OrderWorkshop::PROGRESS_DONE,
            ], true)
            || $order->qualityControlReports->isNotEmpty()
            || $order->workshopHandover !== null
            || $archivedParentTask;
    }
}
