<?php

namespace App\Models;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const PRIORITY_LOW = 'rendah';
    public const PRIORITY_MEDIUM = 'sedang';
    public const PRIORITY_HIGH = 'tinggi';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nomor_order',
        'nama_pekerjaan',
        'unit_kerja',
        'seksi',
        'deskripsi',
        'prioritas',
        'catatan_status',
        'tanggal_order',
        'target_selesai',
        'catatan',
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
            'catatan_status' => OrderUserNoteStatus::class,
            'tanggal_order' => 'date',
            'target_selesai' => 'date',
        ];
    }

    /**
     * Use nomor_order for route model binding URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'nomor_order';
    }

    /**
     * Get the available priorities.
     *
     * @return array<string, string>
     */
    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'Rendah',
            self::PRIORITY_MEDIUM => 'Sedang',
            self::PRIORITY_HIGH => 'Tinggi',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    /**
     * Get the current priority label.
     */
    public function priorityLabel(): string
    {
        return static::priorityOptions()[$this->prioritas] ?? ucfirst((string) $this->prioritas);
    }

    /**
     * Get the priority badge classes.
     */
    public function priorityBadgeClasses(): string
    {
        return match ($this->prioritas) {
            self::PRIORITY_LOW => 'bg-slate-100 text-slate-700',
            self::PRIORITY_MEDIUM => 'bg-amber-100 text-amber-700',
            self::PRIORITY_HIGH => 'bg-orange-100 text-orange-700',
            self::PRIORITY_URGENT => 'bg-rose-100 text-rose-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    /**
     * Get compact priority labels for inline controls.
     */
    public static function priorityControlOptions(): array
    {
        return [
            self::PRIORITY_URGENT => 'Emergency',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_LOW => 'Low',
        ];
    }

    /**
     * Get predefined user note options by status.
     *
     * @return array<string, list<string>>
     */
    public static function userNoteDetailOptions(): array
    {
        return [
            OrderUserNoteStatus::ApprovedJasa->value => [
                'Jasa Fabrikasi',
                'Jasa Konstruksi',
                'Jasa Pengerjaan Mesin',
            ],
            OrderUserNoteStatus::ApprovedWorkshop->value => [
                'Regu Fabrikasi',
                'Regu Bengkel (Refurbish)',
            ],
            OrderUserNoteStatus::ApprovedWorkshopJasa->value => [
                'Jasa Fabrikasi',
                'Jasa Konstruksi',
                'Jasa Pengerjaan Mesin',
                'Regu Fabrikasi',
                'Regu Bengkel (Refurbish)',
            ],
        ];
    }

    /**
     * Scope a query to search by nomor_order and nama_pekerjaan.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($search) {
            $builder
                ->where('nomor_order', 'like', "%{$search}%")
                ->orWhere('nama_pekerjaan', 'like', "%{$search}%");
        });
    }

    /**
     * Get the user who created the order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the documents for the order.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(OrderDocument::class)->latest('uploaded_at');
    }

    /**
     * Get the scope of work for the order.
     */
    public function scopeOfWork(): HasOne
    {
        return $this->hasOne(OrderScopeOfWork::class);
    }

    /**
     * Get workshop processing data for the order.
     */
    public function orderWorkshop(): HasOne
    {
        return $this->hasOne(OrderWorkshop::class);
    }

    /**
     * Get the document types that have been uploaded.
     *
     * @return list<string>
     */
    public function uploadedDocumentTypes(): array
    {
        return $this->documents
            ->pluck('jenis_dokumen')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Determine if all required documents are complete.
     */
    public function hasCompleteRequiredDocuments(): bool
    {
        $uploadedTypes = $this->uploadedDocumentTypes();

        foreach (OrderDocumentType::required() as $type) {
            if (! in_array($type->value, $uploadedTypes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the document completeness summary.
     */
    public function documentCompletionRatio(): string
    {
        $total = count(OrderDocumentType::required());
        $completed = count(array_intersect(
            OrderDocumentType::values(),
            $this->uploadedDocumentTypes(),
        ));

        return "{$completed}/{$total}";
    }

    /**
     * Get the document completion percentage.
     */
    public function documentCompletionPercentage(): int
    {
        $total = count(OrderDocumentType::required());

        if ($total === 0) {
            return 0;
        }

        $completed = count(array_intersect(
            OrderDocumentType::values(),
            $this->uploadedDocumentTypes(),
        ));

        return (int) round(($completed / $total) * 100);
    }
}
