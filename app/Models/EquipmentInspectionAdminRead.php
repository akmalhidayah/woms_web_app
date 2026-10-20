<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentInspectionAdminRead extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_user_id',
        'equipment_inspection_id',
        'document_version',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return ['document_version' => 'integer', 'viewed_at' => 'datetime'];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(EquipmentInspection::class, 'equipment_inspection_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
