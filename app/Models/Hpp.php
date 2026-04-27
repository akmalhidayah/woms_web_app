<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        if ($this->relationLoaded('activeSignature') && $this->activeSignature) {
            return $this->activeSignature->role_label;
        }

        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_APPROVED => 'Selesai',
            self::STATUS_REJECTED => 'Rejected',
            default => $this->approval_flow[0] ?? 'Menunggu review',
        };
    }

    public function isEditable(): bool
    {
        if ($this->status === self::STATUS_DRAFT) {
            return true;
        }

        if ($this->status !== self::STATUS_IN_REVIEW) {
            return false;
        }

        if ($this->relationLoaded('signatures')) {
            return $this->signatures->isEmpty();
        }

        return ! $this->signatures()->exists();
    }

    public function isDeletable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_IN_REVIEW,
            self::STATUS_REJECTED,
        ], true);
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

    public function budgetVerification(): HasOne
    {
        return $this->hasOne(BudgetVerification::class);
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(HppSignature::class)->orderBy('step_order');
    }

    public function activeSignature(): HasOne
    {
        return $this->hasOne(HppSignature::class)
            ->where('status', HppSignature::STATUS_PENDING)
            ->orderBy('step_order');
    }

    public function signedSignatures(): HasMany
    {
        return $this->hasMany(HppSignature::class)
            ->where('status', HppSignature::STATUS_SIGNED)
            ->orderBy('step_order');
    }

    public function approvalProgressPercent(): int
    {
        $signatures = $this->approvalSignatureCollection();
        $total = $signatures->count();

        if ($total === 0) {
            return 0;
        }

        $completed = $signatures
            ->filter(fn (HppSignature $signature): bool => $signature->isSigned() || $signature->isSkipped())
            ->count();

        return (int) round(($completed / $total) * 100);
    }

    public function approvalSignedCount(): int
    {
        return $this->approvalSignatureCollection()
            ->filter(fn (HppSignature $signature): bool => $signature->isSigned())
            ->count();
    }

    public function approvalStepCount(): int
    {
        return $this->approvalSignatureCollection()->count();
    }

    public function currentApprovalSignerLabel(): ?string
    {
        return $this->currentActiveSignature()?->role_label;
    }

    public function currentApprovalSignerName(): ?string
    {
        return $this->currentActiveSignature()?->signer_name_snapshot;
    }

    public function approvalCompleted(): bool
    {
        $signatures = $this->approvalSignatureCollection();

        return $signatures->isNotEmpty()
            && $signatures->every(
                fn (HppSignature $signature): bool => $signature->isSigned() || $signature->isSkipped()
            );
    }

    public function latestActiveApprovalLink(): ?string
    {
        return $this->currentActiveSignature()?->approvalUrl();
    }

    public function signedApproverLabelsSummary(int $limit = 4): string
    {
        $signed = $this->approvalSignatureCollection()
            ->filter(fn (HppSignature $signature): bool => $signature->isSigned())
            ->map(fn (HppSignature $signature): string => trim(
                $signature->role_label.' - '.$signature->signer_name_snapshot
            ))
            ->values();

        if ($signed->isEmpty()) {
            return '-';
        }

        $summary = $signed->take($limit)->implode(', ');
        $remaining = $signed->count() - $limit;

        return $remaining > 0 ? "{$summary} +{$remaining} lagi" : $summary;
    }

    public function finalSignedDocumentSignature(): ?HppSignature
    {
        $signatures = $this->approvalSignatureCollection();

        return $signatures->first(function (HppSignature $signature): bool {
            return $signature->role_key === 'dirops' && $signature->hasUploadedSignedDocument();
        });
    }

    public function hasFinalSignedDocument(): bool
    {
        return $this->finalSignedDocumentSignature() !== null;
    }

    private function currentActiveSignature(): ?HppSignature
    {
        if ($this->relationLoaded('activeSignature') && $this->activeSignature) {
            return $this->activeSignature;
        }

        if ($this->relationLoaded('signatures')) {
            return $this->signatures->first(
                fn (HppSignature $signature): bool => $signature->isPending()
            );
        }

        return $this->activeSignature()->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, HppSignature>
     */
    private function approvalSignatureCollection()
    {
        if ($this->relationLoaded('signatures')) {
            return $this->signatures;
        }

        return $this->signatures()->get();
    }
}
