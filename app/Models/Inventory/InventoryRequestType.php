<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryRequestType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'requires_damaged_photo',
        'requires_new_item_photo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_damaged_photo' => 'boolean',
            'requires_new_item_photo' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'inventory_request_type_id');
    }
}
