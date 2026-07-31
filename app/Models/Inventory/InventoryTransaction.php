<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'inventory_item_id',
        'inventory_user_id',
        'woms_user_id',
        'inventory_request_type_id',
        'transaction_type',
        'quantity',
        'stock_before',
        'stock_after',
        'purpose',
        'notes',
        'reference_number',
        'source',
        'item_uid_snapshot',
        'item_name_snapshot',
        'unit_snapshot',
        'transaction_at',
        'legacy_id',
        'legacy_payload',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
            'transaction_at' => 'datetime',
            'legacy_payload' => 'array',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function inventoryUser(): BelongsTo
    {
        return $this->belongsTo(InventoryUser::class, 'inventory_user_id');
    }

    public function womsUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'woms_user_id');
    }

    public function requestType(): BelongsTo
    {
        return $this->belongsTo(InventoryRequestType::class, 'inventory_request_type_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InventoryTransactionAttachment::class, 'inventory_transaction_id');
    }
}
