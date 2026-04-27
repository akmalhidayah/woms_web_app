<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HppApprovalSetting extends Model
{
    protected $fillable = [
        'planner_control_user_id',
        'counter_part_unit_work_id',
        'counter_part_section_id',
        'dirops_user_id',
    ];

    public function plannerControl(): BelongsTo
    {
        return $this->belongsTo(User::class, 'planner_control_user_id');
    }

    public function counterPartUnit(): BelongsTo
    {
        return $this->belongsTo(UnitWork::class, 'counter_part_unit_work_id');
    }

    public function counterPartSection(): BelongsTo
    {
        return $this->belongsTo(UnitWorkSection::class, 'counter_part_section_id');
    }

    public function dirops(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dirops_user_id');
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}