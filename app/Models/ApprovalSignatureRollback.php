<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalSignatureRollback extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_type',
        'document_id',
        'signature_type',
        'signature_id',
        'step_order',
        'role_key',
        'role_label',
        'signer_user_id',
        'signer_name',
        'rollback_by',
        'rollback_reason',
        'rolled_back_at',
        'affected_signature_ids',
        'previous_payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_id' => 'integer',
            'signature_id' => 'integer',
            'step_order' => 'integer',
            'signer_user_id' => 'integer',
            'rollback_by' => 'integer',
            'rolled_back_at' => 'datetime',
            'affected_signature_ids' => 'array',
            'previous_payload' => 'array',
        ];
    }

    public function rollbackBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rollback_by');
    }
}
