<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentInspectionUnit extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_RETIRED = 'retired';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_RETIRED];

    protected $fillable = [
        'equipment_inspection_form_id',
        'asset_code',
        'name',
        'location',
        'description',
        'image_path',
        'image_mime_type',
        'image_size',
        'image_sha256',
        'public_code',
        'lifecycle_status',
        'public_enabled',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['image_path', 'image_sha256', 'cilt_path'];

    protected function casts(): array
    {
        return [
            'public_enabled' => 'boolean',
            'sort_order' => 'integer',
            'image_size' => 'integer',
            'cilt_size' => 'integer',
            'cilt_updated_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_code';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('lifecycle_status', self::STATUS_ACTIVE);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EquipmentInspectionForm::class, 'equipment_inspection_form_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(EquipmentInspectionUnitLink::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EquipmentInspectionUnitLog::class)->latest('id');
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
