<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutlineAgreementHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'outline_agreement_id',
        'revision_no',
        'tipe_perubahan',
        'nilai_tambahan',
        'periode_start',
        'periode_end',
        'snapshot_total_nilai',
        'snapshot_period_start',
        'snapshot_period_end',
        'keterangan',
        'payload_json',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'nilai_tambahan' => 'decimal:2',
            'snapshot_total_nilai' => 'decimal:2',
            'periode_start' => 'date',
            'periode_end' => 'date',
            'snapshot_period_start' => 'date',
            'snapshot_period_end' => 'date',
            'payload_json' => 'array',
        ];
    }

    public function outlineAgreement(): BelongsTo
    {
        return $this->belongsTo(OutlineAgreement::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return match ($this->tipe_perubahan) {
            OutlineAgreement::CHANGE_INITIAL => 'Initial',
            OutlineAgreement::CHANGE_EXTEND => 'Extend',
            OutlineAgreement::CHANGE_ADD_VALUE => 'Add Value',
            OutlineAgreement::CHANGE_EXTEND_AND_ADD_VALUE => 'Extend + Add Value',
            OutlineAgreement::CHANGE_REVISION => 'Revision',
            default => ucfirst(str_replace('_', ' ', $this->tipe_perubahan)),
        };
    }
}
