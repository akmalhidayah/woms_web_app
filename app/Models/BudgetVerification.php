<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetVerification extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'Tersedia';

    public const STATUS_UNAVAILABLE = 'Tidak Tersedia';

    public const STATUS_WAITING = 'Menunggu';

    public const STATUS_WAITING_BAST = 'Menunggu Proses BAST';

    /**
     * @var list<string>
     */
    protected $touches = ['hpp'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'hpp_id',
        'status_anggaran',
        'kategori_item',
        'kategori_biaya',
        'cost_element',
        'catatan',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    public static function statusAnggaranOptions(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Tersedia',
            self::STATUS_UNAVAILABLE => 'Tidak Tersedia',
            self::STATUS_WAITING => 'Menunggu Anggaran',
            self::STATUS_WAITING_BAST => 'Menunggu Proses BAST',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function kategoriItemOptions(): array
    {
        return [
            'spare part' => 'Spare Part',
            'jasa' => 'Jasa',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function kategoriBiayaOptions(): array
    {
        return [
            'pemeliharaan' => 'Pemeliharaan',
            'non pemeliharaan' => 'Non Pemeliharaan',
            'capex' => 'Capex',
        ];
    }

    public function scopeReadyForPurchaseOrder(Builder $query): Builder
    {
        return $query
            ->whereIn('status_anggaran', [
                self::STATUS_AVAILABLE,
                self::STATUS_WAITING_BAST,
            ])
            ->whereNotNull('kategori_item')
            ->whereRaw("TRIM(kategori_item) <> ''")
            ->whereNotNull('kategori_biaya')
            ->whereRaw("TRIM(kategori_biaya) <> ''")
            ->whereNotNull('cost_element')
            ->whereRaw("TRIM(cost_element) <> ''");
    }

    public function isReadyForPurchaseOrder(): bool
    {
        return in_array($this->status_anggaran, [
            self::STATUS_AVAILABLE,
            self::STATUS_WAITING_BAST,
        ], true)
            && filled(trim((string) $this->kategori_item))
            && filled(trim((string) $this->kategori_biaya))
            && filled(trim((string) $this->cost_element));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function hpp(): BelongsTo
    {
        return $this->belongsTo(Hpp::class);
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
