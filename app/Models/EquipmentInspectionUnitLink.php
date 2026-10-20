<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentInspectionUnitLink extends Model
{
    public const SOURCE_DIRECT = 'direct';

    public const SOURCE_ADMIN_LEGACY = 'admin_legacy';

    protected $fillable = [
        'equipment_inspection_unit_id',
        'equipment_inspection_id',
        'link_source',
        'unit_code_snapshot',
        'unit_name_snapshot',
        'linked_by',
        'linked_at',
    ];

    protected function casts(): array
    {
        return ['linked_at' => 'datetime'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(EquipmentInspectionUnit::class, 'equipment_inspection_unit_id');
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(EquipmentInspection::class, 'equipment_inspection_id');
    }

    public function linker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }
}
