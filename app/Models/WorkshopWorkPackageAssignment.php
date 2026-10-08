<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopWorkPackageAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_package_id', 'bengkel_pic_id', 'pic_name_snapshot',
        'pic_avatar_path_snapshot', 'avatar_position_x', 'avatar_position_y',
        'work_descriptions', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['work_descriptions' => 'array'];
    }

    public function workPackage(): BelongsTo
    {
        return $this->belongsTo(WorkshopWorkPackage::class, 'work_package_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(BengkelPic::class, 'bengkel_pic_id');
    }
}
