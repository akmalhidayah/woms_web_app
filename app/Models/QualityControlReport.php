<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class QualityControlReport extends Model
{
    use HasFactory;

    public const TYPE_FABRICATION = 'fabrication';
    public const TYPE_REFURBISH = 'refurbish';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'bengkel_task_id',
        'type',
        'report_no',
        'report_date',
        'status',
        'payload',
        'created_by',
        'updated_by',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function bengkelTask(): BelongsTo
    {
        return $this->belongsTo(BengkelTask::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(QualityControlReportFile::class)->orderBy('sort_order')->orderBy('id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(QualityControlSignature::class)->orderBy('step_order');
    }

    public function hasSignedApproval(): bool
    {
        return $this->signatures()
            ->where('status', QualityControlSignature::STATUS_SIGNED)
            ->exists();
    }

    public function hasApprovalStarted(): bool
    {
        if ($this->relationLoaded('signatures')) {
            return $this->signatures->contains(
                fn (QualityControlSignature $signature): bool => in_array($signature->status, [
                    QualityControlSignature::STATUS_PENDING,
                    QualityControlSignature::STATUS_SIGNED,
                ], true)
            );
        }

        return $this->signatures()
            ->whereIn('status', [
                QualityControlSignature::STATUS_PENDING,
                QualityControlSignature::STATUS_SIGNED,
            ])
            ->exists();
    }

    public function approvalCompleted(): bool
    {
        $signatures = $this->approvalSignatureCollection();

        return $signatures->isNotEmpty()
            && $signatures->every(fn (QualityControlSignature $signature): bool => $signature->isSigned());
    }

    public function approvalStatus(): string
    {
        if ($this->approvalCompleted()) {
            return 'approved';
        }

        if ($this->hasApprovalStarted()) {
            return 'in_review';
        }

        return $this->status === self::STATUS_SUBMITTED ? 'submitted' : 'draft';
    }

    public function approvalSignedCount(): int
    {
        return $this->approvalSignatureCollection()
            ->filter(fn (QualityControlSignature $signature): bool => $signature->isSigned())
            ->count();
    }

    public function approvalStepCount(): int
    {
        return $this->approvalSignatureCollection()->count();
    }

    public function approvalProgressPercent(): int
    {
        $signatures = $this->approvalSignatureCollection();
        $total = $signatures->count();

        if ($total === 0) {
            return 0;
        }

        $signed = $signatures
            ->filter(fn (QualityControlSignature $signature): bool => $signature->isSigned())
            ->count();

        return (int) round(($signed / $total) * 100);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'payload' => 'array',
        ];
    }

    /**
     * @return Collection<int, QualityControlSignature>
     */
    private function approvalSignatureCollection(): Collection
    {
        if ($this->relationLoaded('signatures')) {
            return $this->signatures;
        }

        return $this->signatures()->get();
    }
}
