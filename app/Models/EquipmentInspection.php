<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentInspection extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_READY = 'ready_for_approval';
    public const STATUS_MANAGER = 'pending_manager';
    public const STATUS_LEADER = 'pending_leader';
    public const STATUS_REVISION = 'revision_required';
    public const STATUS_APPROVED = 'approved';

    public const STATUS_LABELS = [self::STATUS_DRAFT => 'Draft', self::STATUS_READY => 'Menunggu Inisialisasi Approval',
        self::STATUS_MANAGER => 'Menunggu Manager Workshop', self::STATUS_LEADER => 'Menunggu Leader Gugus',
        self::STATUS_REVISION => 'Perlu Revisi', self::STATUS_APPROVED => 'Selesai'];

    protected $guarded = ['id'];
    protected $hidden = ['final_pdf_path', 'final_pdf_sha256'];

    protected function casts(): array
    {
        return [
            'template_snapshot' => 'array',
            'inspection_date' => 'date',
            'signed_at' => 'datetime',
            'document_version' => 'integer',
            'lock_version' => 'integer',
            'returned_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EquipmentInspectionAnswer::class)->orderBy('position')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EquipmentInspectionAttachment::class)->orderBy('id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(EquipmentInspectionSignature::class)->orderBy('step');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EquipmentInspectionLog::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(EquipmentInspectionApproval::class)->orderBy('document_version')->orderBy('step_order');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->signed_at === null;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
