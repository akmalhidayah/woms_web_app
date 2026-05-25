<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BengkelTask extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'job_name',
        'notification_number',
        'unit_work',
        'seksi',
        'usage_plan_date',
        'catatan',
        'is_completed',
        'progress_status',
        'pending_reason',
        'person_in_charge',
        'person_in_charge_profiles',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime_type',
        'attachment_size',
        'archived_at',
        'archived_order_id',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'attachment_url',
        'attachment_display_name',
        'attachment_is_image',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function archivedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'archived_order_id');
    }

    public function effectiveProgressStatus(): ?string
    {
        return $this->order?->orderWorkshop?->progress_status ?: $this->progress_status;
    }

    public function effectiveProgressLabel(): string
    {
        return OrderWorkshop::progressOptions()[$this->effectiveProgressStatus()] ?? 'Menunggu Jadwal';
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path || ! Storage::disk('public')->exists($this->attachment_path)) {
            return null;
        }

        return route('admin.bengkel-tasks.attachment', [
            'bengkel_task' => $this,
            'v' => substr(md5($this->attachment_path.'|'.($this->updated_at?->toIso8601String() ?? '')), 0, 12),
        ], false);
    }

    public function getAttachmentDisplayNameAttribute(): ?string
    {
        return $this->attachment_original_name ?: ($this->attachment_path ? basename($this->attachment_path) : null);
    }

    public function getAttachmentIsImageAttribute(): bool
    {
        return in_array($this->attachment_mime_type, ['image/jpeg', 'image/png'], true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage_plan_date' => 'date',
            'is_completed' => 'boolean',
            'person_in_charge' => 'array',
            'person_in_charge_profiles' => 'array',
            'attachment_size' => 'integer',
            'archived_at' => 'datetime',
        ];
    }
}
