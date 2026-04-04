<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitWork extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'department_id',
        'name',
        'senior_manager_id',
    ];

    /**
     * Scope a query to search by unit name, department, or section.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($search) {
            $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('department', fn (Builder $departmentQuery) => $departmentQuery->where('name', 'like', "%{$search}%"))
                ->orWhereHas('sections', fn (Builder $sectionQuery) => $sectionQuery->where('name', 'like', "%{$search}%"));
        });
    }

    /**
     * Get the department that owns the unit.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the senior manager for the unit.
     */
    public function seniorManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senior_manager_id');
    }

    /**
     * Get the sections for the unit.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(UnitWorkSection::class)->orderBy('name');
    }
}
