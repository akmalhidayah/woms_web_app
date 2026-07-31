<?php

namespace App\Http\Requests\Admin\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => ['required', 'integer', Rule::exists('inventory_items', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'adjustment_type' => ['required', Rule::in(['adjustment_in', 'adjustment_out'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:9223372036854775807'],
            'reason' => ['required', 'string', 'max:2000'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}
