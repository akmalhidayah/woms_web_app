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

    public const PRIORITY_LOW = 'medium_gt_10_hari';
    public const PRIORITY_MEDIUM = 'high_gt_7_sd_10_hari';
    public const PRIORITY_HIGH = 'emergency_lte_7_hari';
    public const PRIORITY_URGENT = 'emergency_unplan_overhaul';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nomor_order',
        'notifikasi',
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
            self::PRIORITY_URGENT => 'Emergency (Unplan Overhaul)',
            self::PRIORITY_HIGH => 'Emergency (<= 7 Hari)',
            self::PRIORITY_MEDIUM => 'High (> 7 Hari s/d 10 Hari)',
            self::PRIORITY_LOW => 'Medium (> 10 Hari)',
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
            self::PRIORITY_LOW => 'bg-blue-100 text-blue-700',
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
            self::PRIORITY_URGENT => 'Emergency (Unplan Overhaul)',
            self::PRIORITY_HIGH => 'Emergency (<= 7 Hari)',
            self::PRIORITY_MEDIUM => 'High (> 7 Hari s/d 10 Hari)',
            self::PRIORITY_LOW => 'Medium (> 10 Hari)',
        ];
    }

    /**
     * Get the primary priority groups shown in the form.
     *
     * @return array<string, string>
     */
    public static function priorityPrimaryOptions(): array
    {
        return [
            'emergency' => 'Emergency',
            'high' => 'High (> 7 Hari s/d 10 Hari)',
            'medium' => 'Medium (> 10 Hari)',
        ];
    }

    /**
     * Get the emergency detail options shown when Emergency is selected.
     *
     * @return array<string, string>
     */
    public static function priorityEmergencyOptions(): array
    {
        return [
            self::PRIORITY_URGENT => 'Unplan Overhaul',
            self::PRIORITY_HIGH => '<= 7 Hari',
        ];
    }

    /**
     * Resolve the primary group from the stored priority value.
     */
    public static function priorityPrimaryFor(?string $priority): string
    {
        return match ($priority) {
            self::PRIORITY_URGENT, self::PRIORITY_HIGH => 'emergency',
            self::PRIORITY_MEDIUM => 'high',
            default => 'medium',
        };
    }

    /**
     * Resolve the emergency detail selection from the stored priority value.
     */
    public static function priorityEmergencyFor(?string $priority): string
    {
        return $priority === self::PRIORITY_URGENT
            ? self::PRIORITY_URGENT
            : self::PRIORITY_HIGH;
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
                ->orWhere('notifikasi', 'like', "%{$search}%")
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
     * Get HPP records created from the order.
     */
    public function hpps(): HasMany
    {
        return $this->hasMany(Hpp::class);
    }

    /**
     * Get BAST/LHPP documents created for the order.
     */
    public function lhppBasts(): HasMany
    {
        return $this->hasMany(LhppBast::class);
    }

    public function garansi(): HasOne
    {
        return $this->hasOne(Garansi::class);
    }

    /**
     * Get the latest HPP for the order.
     */
    public function latestHpp(): HasOne
    {
        return $this->hasOne(Hpp::class)->latestOfMany();
    }

    /**
     * Get the scope of work for the order.
     */
    public function scopeOfWork(): HasOne
    {
        return $this->hasOne(OrderScopeOfWork::class);
    }

    /**
     * Get the initial work document for the order.
     */
    public function initialWork(): HasOne
    {
        return $this->hasOne(InitialWork::class);
    }

    /**
     * Get workshop processing data for the order.
     */
    public function orderWorkshop(): HasOne
    {
        return $this->hasOne(OrderWorkshop::class);
    }

    /**
     * Get budget verification data for the order.
     */
    public function budgetVerification(): HasOne
    {
        return $this->hasOne(BudgetVerification::class);
    }

    /**
     * Get purchase order data for the order.
     */
    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    /**
     * Get the latest purchase order data for the order.
     */
    public function latestPurchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class)->latestOfMany();
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
            ->map(static fn ($type) => $type instanceof OrderDocumentType ? $type->value : (string) $type)
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
