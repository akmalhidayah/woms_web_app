<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitWorkSection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'unit_work_id',
        'name',
        'manager_id',
    ];

    /**
     * Get the unit that owns the section.
     */
    public function unitWork(): BelongsTo
    {
        return $this->belongsTo(UnitWork::class);
    }

    /**
     * Get the manager for the section.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
