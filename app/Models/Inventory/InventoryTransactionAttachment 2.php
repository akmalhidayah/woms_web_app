<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransactionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_transaction_id',
        'attachment_type',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id');
    }
}
