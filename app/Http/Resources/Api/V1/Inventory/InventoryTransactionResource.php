<?php

namespace App\Http\Resources\Api\V1\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'transaction_type' => $this->transaction_type,
            'quantity' => (int) $this->quantity,
            'stock_before' => (int) $this->stock_before,
            'stock_after' => (int) $this->stock_after,
            'purpose' => $this->purpose,
            'notes' => $this->notes,
            'source' => $this->source,
            'item' => [
                'uid' => $this->item_uid_snapshot,
                'name' => $this->item_name_snapshot,
                'unit' => $this->unit_snapshot,
                'item_type' => $this->item?->item_type,
            ],
            'request_type' => new InventoryRequestTypeResource($this->whenLoaded('requestType')),
            'transaction_at' => $this->transaction_at?->toISOString(),
            'attachments' => InventoryAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
