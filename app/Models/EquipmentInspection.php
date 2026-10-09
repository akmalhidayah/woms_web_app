<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentInspection extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_READY = 'ready_for_approval';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'template_snapshot' => 'array',
            'inspection_date' => 'date',
            'signed_at' => 'datetime',
            'document_version' => 'integer',
            'lock_version' => 'integer',
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

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->signed_at === null;
    }

    public function statusLabel(): string
    {
        return $this->status === self::STATUS_READY ? 'Siap Approval' : 'Draft';
    }
}
