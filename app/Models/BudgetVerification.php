<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetVerification extends Model
{
    use HasFactory;

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
            'Tersedia' => 'Tersedia',
            'Tidak Tersedia' => 'Tidak Tersedia',
            'Menunggu' => 'Menunggu',
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
