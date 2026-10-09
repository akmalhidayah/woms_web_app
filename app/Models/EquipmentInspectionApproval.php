<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentInspectionApproval extends Model
{
    public const LOCKED = 'locked';
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const RETURNED = 'returned';
    public const CANCELLED = 'cancelled';
    public const STATUS_LOCKED = self::LOCKED;
    public const STATUS_PENDING = self::PENDING;

    protected $guarded = ['id'];
    protected $hidden = ['token_hash', 'token_encrypted', 'email_attempt_id'];

    protected function casts(): array
    {
        return ['document_version' => 'integer', 'step_order' => 'integer', 'token_encrypted' => 'encrypted',
            'token_expires_at' => 'datetime', 'activated_at' => 'datetime', 'decided_at' => 'datetime',
            'email_attempted_at' => 'datetime', 'email_sent_at' => 'datetime'];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(EquipmentInspection::class, 'equipment_inspection_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_user_id');
    }

    public function scopeActive(Builder $query, bool $includeExpired = false): Builder
    {
        return $query->where('status', self::PENDING)
            ->where('role_key', EquipmentInspectionSignature::ROLE_MANAGER)
            ->where('step_order', EquipmentInspectionSignature::STEPS[EquipmentInspectionSignature::ROLE_MANAGER])
            ->whereNotNull('token_hash')
            ->when(! $includeExpired, fn (Builder $q) => $q->where('token_expires_at', '>', now()))
            ->whereHas('inspection', function (Builder $q): void {
                $q->whereColumn('equipment_inspections.document_version', 'equipment_inspection_approvals.document_version')
                    ->where('equipment_inspections.status', EquipmentInspection::STATUS_MANAGER);
            });
    }

    public function approvalUrl(): ?string
    {
        return $this->role_key === EquipmentInspectionSignature::ROLE_MANAGER
            && $this->status === self::PENDING && $this->token_expires_at?->isFuture() && $this->token_encrypted
            ? route('approval.equipment-inspection.show', $this->token_encrypted) : null;
    }
}
