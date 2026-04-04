<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class OutlineAgreement extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CLOSED = 'closed';

    public const CHANGE_INITIAL = 'initial';
    public const CHANGE_EXTEND = 'extend';
    public const CHANGE_ADD_VALUE = 'add_value';
    public const CHANGE_EXTEND_AND_ADD_VALUE = 'extend_and_add_value';
    public const CHANGE_REVISION = 'revision';

    protected $fillable = [
        'nomor_oa',
        'unit_work_id',
        'jenis_kontrak',
        'nama_kontrak',
        'nilai_kontrak_awal',
        'periode_awal_start',
        'periode_awal_end',
        'current_total_nilai',
        'current_period_start',
        'current_period_end',
        'latest_history_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'nilai_kontrak_awal' => 'decimal:2',
            'current_total_nilai' => 'decimal:2',
            'periode_awal_start' => 'date',
            'periode_awal_end' => 'date',
            'current_period_start' => 'date',
            'current_period_end' => 'date',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_DRAFT => 'Draft',
        ];
    }

    public static function jenisKontrakOptions(): array
    {
        return [
            'Bengkel Mesin' => [
                'Fabrikasi, Konstruksi dan Pengerjaan Mesin',
            ],
            'Bengkel Listrik' => [
                'Maintenance',
                'Perbaikan',
                'Listrik',
            ],
            'Field Supporting' => [
                'Kontrak Jasa OVH Packer',
                'Kontrak Service',
                'Kontrak Jasa Area Kiln',
                'Kontrak Jasa Mekanikal',
            ],
        ];
    }

    public static function amendmentTypeOptions(): array
    {
        return [
            self::CHANGE_EXTEND => 'Perpanjang Periode',
            self::CHANGE_ADD_VALUE => 'Tambah Nilai Kontrak',
            self::CHANGE_EXTEND_AND_ADD_VALUE => 'Perpanjang + Tambah Nilai',
            self::CHANGE_REVISION => 'Revisi Keterangan',
        ];
    }

    public function unitWork(): BelongsTo
    {
        return $this->belongsTo(UnitWork::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OutlineAgreementHistory::class)->orderByDesc('revision_no');
    }

    public function yearlyTargets(): HasMany
    {
        return $this->hasMany(OutlineAgreementTarget::class)->orderBy('tahun');
    }

    public function latestHistory(): BelongsTo
    {
        return $this->belongsTo(OutlineAgreementHistory::class, 'latest_history_id');
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            self::STATUS_EXPIRED => 'bg-rose-100 text-rose-700 ring-rose-200',
            self::STATUS_CLOSED => 'bg-slate-200 text-slate-700 ring-slate-300',
            default => 'bg-amber-100 text-amber-700 ring-amber-200',
        };
    }

    public function periodeAktifLabel(): string
    {
        $start = $this->current_period_start?->format('d M Y') ?? '-';
        $end = $this->current_period_end?->format('d M Y') ?? '-';

        return "{$start} - {$end}";
    }

    public function totalTambahanValue(): float
    {
        if ($this->relationLoaded('histories')) {
            return (float) $this->histories->sum('nilai_tambahan');
        }

        return (float) $this->histories()->sum('nilai_tambahan');
    }

    public function isExpiringSoon(int $days = 60): bool
    {
        if (! $this->current_period_end) {
            return false;
        }

        return $this->current_period_end->isFuture()
            && $this->current_period_end->lte(now()->addDays($days));
    }

    public function resolvedStatus(): string
    {
        if ($this->status === self::STATUS_CLOSED) {
            return self::STATUS_CLOSED;
        }

        if ($this->current_period_end && $this->current_period_end->lt(Carbon::today())) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_ACTIVE;
    }
}
