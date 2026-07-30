<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uid',
        'item_type',
        'inventory_location_id',
        'inventory_category_id',
        'inventory_subcategory_id',
        'type_category',
        'name',
        'description',
        'size',
        'unit',
        'image_disk',
        'image_path',
        'legacy_image_path',
        'current_stock',
        'minimum_stock',
        'is_active',
        'legacy_source',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:3',
            'minimum_stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(InventorySubcategory::class, 'inventory_subcategory_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'inventory_item_id');
    }
}
