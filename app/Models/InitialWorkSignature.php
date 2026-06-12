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
        'acting_as_label',
        'signer_user_id',
        'delegated_from_user_id',
        'delegated_from_name',
        'delegated_by_user_id',
        'delegated_at',
        'delegation_reason',
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
            'initial_work_id' => 'integer',
            'step_order' => 'integer',
            'signer_user_id' => 'integer',
            'delegated_from_user_id' => 'integer',
            'delegated_by_user_id' => 'integer',
            'delegated_at' => 'datetime',
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

        return $this->appUrl(route('approval.initial-work.show', $this->token_encrypted, false));
    }

    public function displayRoleLabel(): string
    {
        return (string) ($this->acting_as_label ?: $this->role_label);
    }

    public function isDelegated(): bool
    {
        return filled($this->acting_as_label) || filled($this->delegated_from_user_id);
    }

    private function appUrl(string $path): string
    {
        $root = app()->runningInConsole()
            ? (string) config('app.url')
            : request()->getSchemeAndHttpHost();

        return rtrim($root, '/').'/'.ltrim($path, '/');
    }
}
