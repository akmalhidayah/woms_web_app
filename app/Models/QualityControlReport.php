<?php

namespace App\Models;

use App\Support\SignatureImageStorage;
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
        if ($this->status !== self::STATUS_SUBMITTED || ! $this->hasValidMakerSignature()) {
            return false;
        }

        $allSignatures = $this->relationLoaded('signatures')
            ? $this->signatures
            : $this->signatures()->get();
        $requiredRoles = [
            QualityControlSignature::ROLE_WORKSHOP_MANAGER,
            QualityControlSignature::ROLE_USER_MANAGER,
        ];

        if ($allSignatures->count() !== 2 || $allSignatures->contains(
            fn (QualityControlSignature $signature): bool => ! in_array($signature->role_key, $requiredRoles, true)
        )) {
            return false;
        }

        $signatures = $allSignatures;
        $workshop = $signatures->where('role_key', QualityControlSignature::ROLE_WORKSHOP_MANAGER);
        $user = $signatures->where('role_key', QualityControlSignature::ROLE_USER_MANAGER);

        return $workshop->count() === 1
            && $user->count() === 1
            && $workshop->first()->isSigned()
            && $user->first()->isSigned();
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
        if ($this->status !== self::STATUS_SUBMITTED) {
            return 0;
        }

        $makerCount = $this->hasValidMakerSignature() ? 1 : 0;
        $signatures = $this->approvalSignatureCollection();
        $managerCount = collect([
            QualityControlSignature::ROLE_WORKSHOP_MANAGER,
            QualityControlSignature::ROLE_USER_MANAGER,
        ])->filter(function (string $roleKey) use ($signatures): bool {
            $roleSignatures = $signatures->where('role_key', $roleKey);

            return $roleSignatures->count() === 1
                && $roleSignatures->first()->isSigned();
        })->count();

        return min(3, $makerCount + $managerCount);
    }

    public function approvalStepCount(): int
    {
        return $this->status === self::STATUS_SUBMITTED ? 3 : 0;
    }

    public function approvalProgressPercent(): int
    {
        $total = $this->approvalStepCount();

        if ($total === 0) {
            return 0;
        }

        $signed = $this->approvalSignedCount();

        return (int) round(($signed / $total) * 100);
    }

    public function hasValidMakerSignature(): bool
    {
        $signature = $this->payload['signature'] ?? [];
        $signatureData = is_array($signature) ? trim((string) ($signature['signature_data'] ?? '')) : '';

        return $signatureData !== '' && SignatureImageStorage::imageSource($signatureData) !== null;
    }

    public function makerSignature(): array
    {
        $signature = $this->payload['signature'] ?? [];

        return is_array($signature) ? $signature : [];
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
            return $this->signatures
                ->whereIn('role_key', [
                    QualityControlSignature::ROLE_WORKSHOP_MANAGER,
                    QualityControlSignature::ROLE_USER_MANAGER,
                ])
                ->values();
        }

        return $this->signatures()
            ->whereIn('role_key', [
                QualityControlSignature::ROLE_WORKSHOP_MANAGER,
                QualityControlSignature::ROLE_USER_MANAGER,
            ])
            ->get();
    }
}
