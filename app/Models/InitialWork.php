<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'tanggal_initial_work' => 'date',
            'target_penyelesaian' => 'date',
            'functional_location' => 'array',
            'scope_pekerjaan' => 'array',
            'qty' => 'array',
            'stn' => 'array',
            'keterangan' => 'array',
            'progress_pekerjaan' => 'integer',
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
}
