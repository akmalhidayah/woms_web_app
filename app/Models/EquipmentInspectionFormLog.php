<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentInspectionFormLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'equipment_inspection_form_id',
        'actor_user_id',
        'event',
        'form_version',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EquipmentInspectionForm::class, 'equipment_inspection_form_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
