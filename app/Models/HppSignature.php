<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HppSignature extends Model
{
    use HasFactory;

    public const STATUS_LOCKED = 'locked';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_SKIPPED = 'skipped';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'hpp_id',
        'step_order',
        'role_key',
        'role_label',
        'signer_user_id',
        'signer_name_snapshot',
        'signer_position_snapshot',
        'signer_department_snapshot',
        'signer_unit_snapshot',
        'signer_section_snapshot',
        'token',
        'token_hash',
        'token_expires_at',
        'status',
        'opened_at',
        'signed_at',
        'signature_data',
        'signed_document_path',
        'signed_document_original_name',
        'signed_document_mime_type',
        'signed_document_uploaded_at',
        'approval_note',
        'signed_ip',
        'signed_user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'opened_at' => 'datetime',
            'signed_at' => 'datetime',
            'signed_document_uploaded_at' => 'datetime',
        ];
    }

    public function hpp(): BelongsTo
    {
        return $this->belongsTo(Hpp::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_user_id');
    }

    public function scopeActivePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function tokenExpired(): bool
    {
        return $this->token_expires_at !== null && $this->token_expires_at->isPast();
    }

    public function approvalUrl(): ?string
    {
        if (! $this->token || $this->tokenExpired()) {
            return null;
        }

        return route('approval.hpp.show', $this->token);
    }

    public function belongsToRequesterSide(): bool
    {
        return in_array($this->role_key, [
            'manager_peminta',
            'sm_peminta',
            'gm_peminta',
            'workshop_manager_pengendali',
            'planner_control',
            'sm_counter_part',
            'manager_counter_part',
        ], true);
    }

    public function belongsToControllerSide(): bool
    {
        return ! $this->belongsToRequesterSide();
    }

    public function noteGroupLabel(): string
    {
        return $this->belongsToRequesterSide() ? 'Catatan Peminta' : 'Catatan Pengendali';
    }

    public function hasUploadedSignedDocument(): bool
    {
        return trim((string) $this->signed_document_path) !== '';
    }
}
