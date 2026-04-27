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
        if (! Schema::hasTable('vendor_work_types')) {
            return self::legacyTipePekerjaanOptions();
        }

        $vendorOptions = VendorWorkType::query()
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();

        return $vendorOptions ?: self::legacyTipePekerjaanOptions();
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
}
