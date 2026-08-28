<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderWorkshop extends Model
{
    use HasFactory;

    public const PREPARATION_WAITING_BUDGET_CONFIRMATION = 'waiting_budget_confirmation';

    public const PREPARATION_WAITING_MATERIAL = 'waiting_material';

    public const PREPARATION_WAITING_BUDGET_TRANSFER = 'waiting_budget_transfer';

    public const PREPARATION_COMPLETED = 'completed';

    public const PROGRESS_MENUNGGU_JADWAL = 'menunggu_jadwal';

    public const PROGRESS_IN_PROGRESS = 'in_progress';

    public const PROGRESS_QUALITY_CONTROL = 'quality_control';

    public const PROGRESS_PENDING = 'pending';

    public const PROGRESS_DONE = 'done';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'preparation_status',
        'preparation_note',
        'progress_status',
        'keterangan_progress',
        'catatan',
    ];

    public static function preparationOptions(): array
    {
        return [
            self::PREPARATION_WAITING_BUDGET_CONFIRMATION => 'Menunggu Konfirmasi Anggaran',
            self::PREPARATION_WAITING_MATERIAL => 'Menunggu Material',
            self::PREPARATION_WAITING_BUDGET_TRANSFER => 'Menunggu Transfer Budget',
            self::PREPARATION_COMPLETED => 'Set Selesai',
        ];
    }

    public function preparationLabel(): string
    {
        return match ($this->preparation_status) {
            self::PREPARATION_WAITING_BUDGET_CONFIRMATION => 'Menunggu Konfirmasi Anggaran',
            self::PREPARATION_WAITING_MATERIAL => 'Menunggu Material',
            self::PREPARATION_WAITING_BUDGET_TRANSFER => 'Menunggu Transfer Budget',
            self::PREPARATION_COMPLETED => 'Persiapan Selesai',
            default => 'Belum Memilih Persiapan',
        };
    }

    public function preparationCompleted(): bool
    {
        return $this->preparation_status === self::PREPARATION_COMPLETED;
    }

    public static function progressOptions(): array
    {
        return [
            self::PROGRESS_MENUNGGU_JADWAL => 'Menunggu Jadwal',
            self::PROGRESS_IN_PROGRESS => 'Sementara Proses',
            self::PROGRESS_QUALITY_CONTROL => 'Proses Quality Control',
            self::PROGRESS_PENDING => 'Pending',
            self::PROGRESS_DONE => 'Selesai',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
