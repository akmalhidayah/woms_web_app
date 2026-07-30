<?php

namespace App\Http\Resources\Api\V1\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'employee_number' => $this->employee_number,
            'phone' => $this->phone,
            'position' => $this->position,
            'department' => $this->department,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'must_change_password' => $this->must_change_password,
            'last_login_at' => $this->last_login_at?->toISOString(),
        ];
    }
}
