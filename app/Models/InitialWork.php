<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class InitialWork extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'outline_agreement_id',
        'outline_agreement',
        'periode_outline_agreement',
        'unit_work_id',
        'unit_work_section_id',
        'unit_kerja_pengendali',
        'seksi_pengendali',
        'departemen_pengendali',
        'nomor_initial_work',
        'nomor_order',
        'notifikasi',
        'nama_pekerjaan',
        'unit_kerja',
        'seksi',
        'kepada_yth',
        'perihal',
        'tanggal_initial_work',
        'functional_location',
        'scope_pekerjaan',
        'qty',
        'stn',
        'keterangan',
        'keterangan_pekerjaan',
        'target_penyelesaian',
        'progress_pekerjaan',
        'tanggal_mulai_pekerjaan',
        'tanggal_selesai_pekerjaan',
        'vendor_note',
        'admin_note',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'outline_agreement_id' => 'integer',
            'unit_work_id' => 'integer',
            'unit_work_section_id' => 'integer',
            'tanggal_initial_work' => 'date',
            'target_penyelesaian' => 'date',
            'functional_location' => 'array',
            'scope_pekerjaan' => 'array',
            'qty' => 'array',
            'stn' => 'array',
            'keterangan' => 'array',
            'progress_pekerjaan' => 'integer',
            'tanggal_mulai_pekerjaan' => 'date',
            'tanggal_selesai_pekerjaan' => 'date',
            'created_by' => 'integer',
        ];
    }

    /**
     * Get the order for the initial work.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the creator for the initial work.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function outlineAgreement(): BelongsTo
    {
        return $this->belongsTo(OutlineAgreement::class);
    }

    public function unitWork(): BelongsTo
    {
        return $this->belongsTo(UnitWork::class);
    }

    public function unitWorkSection(): BelongsTo
    {
        return $this->belongsTo(UnitWorkSection::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(InitialWorkSignature::class)->orderBy('step_order');
    }

    public function hasSignedApproval(): bool
    {
        return $this->signatures()
            ->where('status', InitialWorkSignature::STATUS_SIGNED)
            ->exists();
    }

    public function hasApprovalStarted(): bool
    {
        if ($this->relationLoaded('signatures')) {
            return $this->signatures->contains(
                fn (InitialWorkSignature $signature): bool => in_array($signature->status, [
                    InitialWorkSignature::STATUS_PENDING,
                    InitialWorkSignature::STATUS_SIGNED,
                ], true)
            );
        }

        return $this->signatures()
            ->whereIn('status', [
                InitialWorkSignature::STATUS_PENDING,
                InitialWorkSignature::STATUS_SIGNED,
            ])
            ->exists();
    }

    public function approvalCompleted(): bool
    {
        $signatures = $this->approvalSignatureCollection();

        return $signatures->isNotEmpty()
            && $signatures->every(fn (InitialWorkSignature $signature): bool => $signature->isSigned());
    }

    public function approvalStatus(): string
    {
        if ($this->approvalCompleted()) {
            return 'approved';
        }

        if ($this->hasApprovalStarted()) {
            return 'in_review';
        }

        return 'draft';
    }

    public function approvalSignedCount(): int
    {
        return $this->approvalSignatureCollection()
            ->filter(fn (InitialWorkSignature $signature): bool => $signature->isSigned())
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
            ->filter(fn (InitialWorkSignature $signature): bool => $signature->isSigned())
            ->count();

        return (int) round(($signed / $total) * 100);
    }

    /**
     * @return Collection<int, InitialWorkSignature>
     */
    private function approvalSignatureCollection(): Collection
    {
        if ($this->relationLoaded('signatures')) {
            return $this->signatures;
        }

        return $this->signatures()->get();
    }
}
