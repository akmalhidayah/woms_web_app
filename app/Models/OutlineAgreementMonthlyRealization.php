<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutlineAgreementMonthlyRealization extends Model
{
    use HasFactory;

    protected $fillable = [
        'outline_agreement_id',
        'year',
        'month',
        'pr_po_amount',
        'urgent_amount',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'pr_po_amount' => 'integer',
            'urgent_amount' => 'integer',
        ];
    }

    public function outlineAgreement(): BelongsTo
    {
        return $this->belongsTo(OutlineAgreement::class);
    }
}
