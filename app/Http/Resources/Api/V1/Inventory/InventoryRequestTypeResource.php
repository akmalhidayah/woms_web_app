<?php

namespace App\Http\Resources\Api\V1\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryRequestTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'requires_damaged_photo' => $this->requires_damaged_photo,
            'requires_new_item_photo' => $this->requires_new_item_photo,
        ];
    }
}
