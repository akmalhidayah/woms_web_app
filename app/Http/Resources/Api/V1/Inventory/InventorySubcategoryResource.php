<?php

namespace App\Http\Resources\Api\V1\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventorySubcategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_category_id' => $this->inventory_category_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
