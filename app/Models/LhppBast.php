<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class LhppBast extends Model
{
    use HasFactory;

    public const APPROVAL_IN_REVIEW = 'in_review';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'termin_type',
        'parent_lhpp_bast_id',
        'hpp_id',
        'purchase_order_id',
        'nomor_order',
        'notifikasi',
        'purchase_order_number',
        'deskripsi_pekerjaan',
        'tipe_pekerjaan',
        'unit_kerja',
        'seksi',
        'tanggal_bast',
        'tanggal_mulai_pekerjaan',
        'tanggal_selesai_pekerjaan',
        'approval_threshold',
        'nilai_hpp',
        'material_items',
        'service_items',
        'subtotal_material',
        'subtotal_jasa',
        'total_aktual_biaya',
        'termin_1_nilai',
        'termin_2_nilai',
        'termin1_status',
        'termin2_status',
        'quality_control_status',
        'approval_status',
        'approval_case',
        'approval_flow',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_bast' => 'date',
            'tanggal_mulai_pekerjaan' => 'date',
            'tanggal_selesai_pekerjaan' => 'date',
            'nilai_hpp' => 'decimal:2',
            'material_items' => 'array',
            'service_items' => 'array',
            'approval_flow' => 'array',
            'subtotal_material' => 'decimal:2',
            'subtotal_jasa' => 'decimal:2',
            'total_aktual_biaya' => 'decimal:2',
            'termin_1_nilai' => 'decimal:2',
            'termin_2_nilai' => 'decimal:2',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function tipePekerjaanOptions(): array
    {
        if (! Schema::hasTable('vendor_work_type_sections')) {
            return self::legacyTipePekerjaanOptions();
        }

        $vendorSectionOptions = VendorWorkTypeSection::query()
            ->whereNotNull('name')
            ->whereRaw("TRIM(name) <> ''")
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();

        return $vendorSectionOptions ?: self::legacyTipePekerjaanOptions();
    }

    public static function tipePekerjaanLabel(?string $value): string
    {
        if (blank($value)) {
            return '-';
        }

        $options = self::tipePekerjaanOptions() + self::legacyTipePekerjaanOptions();

        return $options[$value] ?? $value;
    }

    /**
     * @return array<string, string>
     */
    public static function legacyTipePekerjaanOptions(): array
    {
        return [
            'pekerjaan_fabrikasi' => 'Pekerjaan Fabrikasi',
            'pekerjaan_konstruksi' => 'Pekerjaan Konstruksi',
            'pekerjaan_mesin' => 'Pekerjaan Mesin',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function hpp(): BelongsTo
    {
        return $this->belongsTo(Hpp::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function parentLhppBast(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_lhpp_bast_id');
    }

    public function childLhppBasts(): HasMany
    {
        return $this->hasMany(self::class, 'parent_lhpp_bast_id');
    }

    public function terminTwo(): HasOne
    {
        return $this->hasOne(self::class, 'parent_lhpp_bast_id')->where('termin_type', 'termin_2');
    }

    public function lpjPpl(): HasOne
    {
        return $this->hasOne(LpjPpl::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(LhppBastImage::class)->latest('id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(LhppBastSignature::class)->orderBy('step_order');
    }

    public function activeSignature(): HasOne
    {
        return $this->hasOne(LhppBastSignature::class)
            ->where('status', LhppBastSignature::STATUS_PENDING)
            ->orderBy('step_order');
    }

    public function garansi(): HasOne
    {
        return $this->hasOne(Garansi::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvalProgressPercent(): int
    {
        $signatures = $this->approvalSignatureCollection();
        $total = $signatures->count();

        if ($total === 0) {
            return 0;
        }

        $completed = $signatures
            ->filter(fn (LhppBastSignature $signature): bool => $signature->isSigned() || $signature->isSkipped())
            ->count();

        return (int) round(($completed / $total) * 100);
    }

    public function approvalSignedCount(): int
    {
        return $this->approvalSignatureCollection()
            ->filter(fn (LhppBastSignature $signature): bool => $signature->isSigned())
            ->count();
    }

    public function approvalStepCount(): int
    {
        return $this->approvalSignatureCollection()->count();
    }

    public function approvalCompleted(): bool
    {
        $signatures = $this->approvalSignatureCollection();

        return $signatures->isNotEmpty()
            && $signatures->every(
                fn (LhppBastSignature $signature): bool => $signature->isSigned() || $signature->isSkipped()
            );
    }

    public function latestActiveApprovalLink(): ?string
    {
        return $this->currentActiveSignature()?->approvalUrl();
    }

    public function finalSignedDocumentSignature(): ?LhppBastSignature
    {
        return $this->approvalSignatureCollection()->first(function (LhppBastSignature $signature): bool {
            return $signature->role_key === 'dirops' && $signature->hasUploadedSignedDocument();
        });
    }

    public function hasFinalSignedDocument(): bool
    {
        return $this->finalSignedDocumentSignature() !== null;
    }

    private function currentActiveSignature(): ?LhppBastSignature
    {
        if ($this->relationLoaded('activeSignature') && $this->activeSignature) {
            return $this->activeSignature;
        }

        if ($this->relationLoaded('signatures')) {
            return $this->signatures->first(
                fn (LhppBastSignature $signature): bool => $signature->isPending()
            );
        }

        return $this->activeSignature()->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, LhppBastSignature>
     */
    private function approvalSignatureCollection()
    {
        if ($this->relationLoaded('signatures')) {
            return $this->signatures;
        }

        return $this->signatures()->get();
    }
}
