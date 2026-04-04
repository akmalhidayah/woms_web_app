<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutlineAgreementTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'outline_agreement_id',
        'tahun',
        'nilai_target',
    ];

    protected function casts(): array
    {
        return [
            'nilai_target' => 'decimal:2',
        ];
    }

    public function outlineAgreement(): BelongsTo
    {
        return $this->belongsTo(OutlineAgreement::class);
    }
}
