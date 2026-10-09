<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentInspectionAttachment extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['path', 'sha256'];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(EquipmentInspection::class, 'equipment_inspection_id');
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(EquipmentInspectionAnswer::class, 'equipment_inspection_answer_id');
    }
}
