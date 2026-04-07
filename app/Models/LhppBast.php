<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LhppBast extends Model
{
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'nomor_order';
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'hpp_id',
        'purchase_order_id',
        'nomor_order',
        'purchase_order_number',
        'deskripsi_pekerjaan',
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

    public function lpjPpl(): HasOne
    {
        return $this->hasOne(LpjPpl::class);
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
