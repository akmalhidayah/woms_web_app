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

    private function appUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
