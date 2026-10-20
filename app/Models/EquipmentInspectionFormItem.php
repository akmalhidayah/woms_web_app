<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentInspectionFormItem extends Model
{
    protected $fillable = [
        'equipment_inspection_form_id',
        'item_key',
        'label',
        'group_key',
        'group_name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EquipmentInspectionForm::class, 'equipment_inspection_form_id');
    }
}
