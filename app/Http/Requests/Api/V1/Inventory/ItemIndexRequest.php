<?php

namespace App\Http\Requests\Api\V1\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'item_type' => ['nullable', Rule::in(['consumable', 'equipment'])],
            'category_id' => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:inventory_subcategories,id'],
            'location_id' => ['nullable', 'integer', 'exists:inventory_locations,id'],
            'stock_status' => ['nullable', Rule::in(['available', 'low', 'out'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', Rule::in(['name', 'uid', 'current_stock', 'newest'])],
        ];
    }
}
