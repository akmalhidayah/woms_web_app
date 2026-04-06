<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hpp extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'outline_agreement_id',
        'nomor_order',
        'nama_pekerjaan',
        'unit_kerja',
        'unit_work_id',
        'cost_centre',
        'kategori_pekerjaan',
        'area_pekerjaan',
        'nilai_hpp_bucket',
        'unit_kerja_pengendali',
        'outline_agreement',
        'periode_outline_agreement',
        'approval_case',
        'approval_flow',
        'item_groups',
        'total_keseluruhan',
        'status',
        'submitted_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approval_flow' => 'array',
            'item_groups' => 'array',
            'total_keseluruhan' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_IN_REVIEW => 'In Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($search) {
            $builder
                ->where('nomor_order', 'like', "%{$search}%")
                ->orWhere('nama_pekerjaan', 'like', "%{$search}%")
                ->orWhere('kategori_pekerjaan', 'like', "%{$search}%")
                ->orWhere('area_pekerjaan', 'like', "%{$search}%")
                ->orWhere('unit_kerja', 'like', "%{$search}%");
        });
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'bg-emerald-100 text-emerald-700',
            self::STATUS_REJECTED => 'bg-rose-100 text-rose-700',
            self::STATUS_IN_REVIEW => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public function currentStepLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_APPROVED => 'Selesai',
            self::STATUS_REJECTED => 'Rejected',
            default => $this->approval_flow[0] ?? 'Menunggu review',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_IN_REVIEW,
        ], true);
    }

    public function isDeletable(): bool
    {
        return $this->isEditable();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function outlineAgreement(): BelongsTo
    {
        return $this->belongsTo(OutlineAgreement::class);
    }

    public function unitWork(): BelongsTo
    {
        return $this->belongsTo(UnitWork::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
