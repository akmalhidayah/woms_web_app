<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InitialWorkSignature extends Model
{
    use HasFactory;

    public const ROLE_MANAGER = 'manager';
    public const ROLE_SENIOR_MANAGER = 'senior_manager';

    public const STATUS_MISSING = 'missing';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SIGNED = 'signed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'initial_work_id',
        'step_order',
        'role_key',
        'role_label',
        'signer_user_id',
        'signer_name',
        'signer_position',
        'source_department',
        'source_unit',
        'source_section',
        'token_hash',
        'token_encrypted',
        'token_expires_at',
        'status',
        'signature_path',
        'signed_at',
        'signed_ip',
        'signed_user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'signed_at' => 'datetime',
            'token_encrypted' => 'encrypted',
        ];
    }

    public function initialWork(): BelongsTo
    {
        return $this->belongsTo(InitialWork::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED;
    }

    public function tokenExpired(): bool
    {
        return $this->token_expires_at !== null && $this->token_expires_at->isPast();
    }

    public function approvalUrl(): ?string
    {
        if (! $this->token_encrypted || $this->tokenExpired()) {
            return null;
        }

        return route('approval.initial-work.show', $this->token_encrypted);
    }
}
