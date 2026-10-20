<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentInspectionUnitLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['equipment_inspection_unit_id', 'actor_user_id', 'event', 'metadata', 'created_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(EquipmentInspectionUnit::class, 'equipment_inspection_unit_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
