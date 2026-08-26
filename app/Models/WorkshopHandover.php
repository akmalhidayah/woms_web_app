<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkshopHandover extends Model
{
    use HasFactory;

    public const STATUS_WAITING_USER_SIGNATURE = 'waiting_user_signature';

    public const STATUS_COMPLETED = 'completed';

    public const PATH_CRITICAL = 'Critical';

    public const PATH_NON_CRITICAL = 'Non-Critical';

    protected $fillable = [
        'order_id',
        'document_no',
        'path',
        'status',
        'handed_over_at',
        'order_no_snapshot',
        'job_name_snapshot',
        'unit_snapshot',
        'section_snapshot',
        'admin_user_id',
        'admin_name_snapshot',
        'admin_position_snapshot',
        'admin_signature_path',
        'admin_signed_at',
        'admin_signed_ip',
        'admin_signed_user_agent',
        'recipient_user_id',
        'recipient_name_snapshot',
        'recipient_position_snapshot',
        'user_signature_path',
        'user_signed_at',
        'user_signed_ip',
        'user_signed_user_agent',
        'photo_paths',
        'token_hash',
        'token_encrypted',
        'token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'handed_over_at' => 'datetime',
            'admin_signed_at' => 'datetime',
            'user_signed_at' => 'datetime',
            'photo_paths' => 'array',
            'token_encrypted' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function isWaitingUserSignature(): bool
    {
        return $this->status === self::STATUS_WAITING_USER_SIGNATURE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function tokenExpired(): bool
    {
        return $this->token_expires_at === null || $this->token_expires_at->isPast();
    }

    public function progress(): string
    {
        return $this->isCompleted() ? '2/2' : '1/2';
    }

    public function approvalUrl(): ?string
    {
        if (! $this->token_encrypted || ! $this->isWaitingUserSignature() || $this->tokenExpired()) {
            return null;
        }

        return route('approval.workshop-handover.show', $this->token_encrypted, false);
    }

    public static function newDocumentNumber(): string
    {
        return 'STB-'.now()->format('Ymd').'-'.Str::upper(Str::random(12));
    }
}
