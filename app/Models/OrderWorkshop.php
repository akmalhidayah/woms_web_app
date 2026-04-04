<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderWorkshop extends Model
{
    use HasFactory;

    public const KONFIRMASI_MATERIAL_READY = 'Material Ready';
    public const KONFIRMASI_MATERIAL_NOT_READY = 'Material Not Ready';

    public const STATUS_ANGGARAN_WAITING_BUDGET = 'Waiting Budget';
    public const STATUS_ANGGARAN_PENDING = 'Pending';
    public const STATUS_ANGGARAN_COMPLETE = 'Complete';

    public const STATUS_MATERIAL_GOOD_ISSUE = 'Good Issue';
    public const STATUS_MATERIAL_TRANSPORT = 'Transport Material';

    public const PROGRESS_MENUNGGU_JADWAL = 'menunggu_jadwal';
    public const PROGRESS_IN_PROGRESS = 'in_progress';
    public const PROGRESS_DONE = 'done';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'konfirmasi_anggaran',
        'keterangan_konfirmasi',
        'status_anggaran',
        'keterangan_anggaran',
        'status_material',
        'keterangan_material',
        'progress_status',
        'keterangan_progress',
        'catatan',
        'nomor_e_korin',
        'status_e_korin',
    ];

    public static function konfirmasiAnggaranOptions(): array
    {
        return [
            self::KONFIRMASI_MATERIAL_READY => 'Material Ready',
            self::KONFIRMASI_MATERIAL_NOT_READY => 'Material Not Ready',
        ];
    }

    public static function statusAnggaranOptions(): array
    {
        return [
            self::STATUS_ANGGARAN_WAITING_BUDGET => 'Waiting Budget',
            self::STATUS_ANGGARAN_PENDING => 'Pending',
            self::STATUS_ANGGARAN_COMPLETE => 'Complete',
        ];
    }

    public static function materialOptions(): array
    {
        return [
            self::STATUS_MATERIAL_GOOD_ISSUE => 'Good Issue',
            self::STATUS_MATERIAL_TRANSPORT => 'Transport Material',
        ];
    }

    public static function progressOptions(): array
    {
        return [
            self::PROGRESS_MENUNGGU_JADWAL => 'Menunggu Jadwal',
            self::PROGRESS_IN_PROGRESS => 'Sementara Proses',
            self::PROGRESS_DONE => 'Selesai',
        ];
    }

    public static function eKorinStatusOptions(): array
    {
        return [
            'waiting_korin' => 'Waiting Korin',
            'waiting_approval' => 'Waiting Approval',
            'waiting_transfer' => 'Waiting Transfer',
            'complete_transfer' => 'Complete Transfer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
