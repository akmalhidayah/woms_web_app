<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentInspectionAnswer extends Model
{
    protected $guarded = ['id'];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(EquipmentInspection::class, 'equipment_inspection_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EquipmentInspectionAttachment::class)->orderBy('id');
    }
}
