<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityControlSignature extends Model
{
    use HasFactory;

    public const ROLE_WORKSHOP_MANAGER = 'workshop_manager';
    public const ROLE_USER_MANAGER = 'user_manager';

    public const STATUS_MISSING = 'missing';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SIGNED = 'signed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'quality_control_report_id',
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
        'signature_data',
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
            'quality_control_report_id' => 'integer',
            'step_order' => 'integer',
            'signer_user_id' => 'integer',
            'delegated_from_user_id' => 'integer',
            'delegated_by_user_id' => 'integer',
            'delegated_at' => 'datetime',
            'token_encrypted' => 'encrypted',
            'token_expires_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    public function qualityControlReport(): BelongsTo
    {
        return $this->belongsTo(QualityControlReport::class);
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

        return $this->appUrl(route('approval.quality-control.show', $this->token_encrypted, false));
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
