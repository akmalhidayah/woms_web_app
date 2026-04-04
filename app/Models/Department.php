<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'general_manager_id',
    ];

    /**
     * Scope a query to search by name.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where('name', 'like', "%{$search}%");
    }

    /**
     * Get the general manager for the department.
     */
    public function generalManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'general_manager_id');
    }

    /**
     * Get the units for the department.
     */
    public function units(): HasMany
    {
        return $this->hasMany(UnitWork::class);
    }
}
