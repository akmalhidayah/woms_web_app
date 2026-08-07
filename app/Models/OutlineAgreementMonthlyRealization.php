<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OutlineAgreementMonthlyRealization extends Model
{
    use HasFactory;

    public const CATEGORY_UNCATEGORIZED = 'belum_dikategorikan';

    protected $fillable = [
        'outline_agreement_id',
        'year',
        'month',
        'kategori_biaya',
        'unit_kerja',
        'seksi',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'amount' => 'integer',
        ];
    }

    public function categoryLabel(): string
    {
        $category = trim((string) $this->kategori_biaya);

        if ($category === self::CATEGORY_UNCATEGORIZED) {
            return 'Belum Dikategorikan';
        }

        if (isset(BudgetVerification::kategoriBiayaOptions()[$category])) {
            return BudgetVerification::kategoriBiayaOptions()[$category];
        }

        return $category !== '' ? Str::headline($category) : 'Belum Dikategorikan';
    }

    public function outlineAgreement(): BelongsTo
    {
        return $this->belongsTo(OutlineAgreement::class);
    }
}
