<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentInspectionForm extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_INACTIVE];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'pic_name',
        'pic_photo_path',
        'pic_photo_mime_type',
        'pic_photo_size',
        'pic_photo_sha256',
        'image_path',
        'image_mime_type',
        'image_size',
        'image_sha256',
        'status',
        'sort_order',
        'version',
        'is_legacy',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['image_path', 'image_sha256', 'pic_photo_path', 'pic_photo_sha256'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'version' => 'integer',
            'image_size' => 'integer',
            'pic_photo_size' => 'integer',
            'is_legacy' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EquipmentInspectionFormItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EquipmentInspectionFormLog::class)->latest('id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(EquipmentInspectionUnit::class)->orderBy('sort_order')->orderBy('id');
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
