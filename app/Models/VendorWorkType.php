<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorWorkType extends Model
{
    use HasFactory;

    public const FIXED_VENDOR_NAME = 'PT. Prima Karya Manunggal';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'unit_work_section_id',
        'manager_id',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(UnitWorkSection::class, 'unit_work_section_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function vendorSections(): HasMany
    {
        return $this->hasMany(VendorWorkTypeSection::class);
    }
}
