<?php

namespace App\Http\Resources\Api\V1\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'item_type' => $this->item_type,
            'type_category' => $this->type_category,
            'name' => $this->name,
            'description' => $this->description,
            'size' => $this->size,
            'unit' => $this->unit,
            'current_stock' => $this->current_stock,
            'minimum_stock' => $this->minimum_stock,
            'stock_status' => $this->stockStatus(),
            'category' => new InventoryCategoryResource($this->whenLoaded('category')),
            'subcategory' => new InventorySubcategoryResource($this->whenLoaded('subcategory')),
            'location' => new InventoryLocationResource($this->whenLoaded('location')),
            'image_url' => filled($this->image_path)
                ? route('api.v1.inventory.items.image', $this->id)
                : null,
            'can_request' => $this->is_active && ! $this->trashed() && (float) $this->current_stock > 0,
        ];
    }

    private function stockStatus(): string
    {
        if ((float) $this->current_stock <= 0) {
            return 'out';
        }

        if ((float) $this->minimum_stock > 0 && (float) $this->current_stock <= (float) $this->minimum_stock) {
            return 'low';
        }

        return 'available';
    }
}
