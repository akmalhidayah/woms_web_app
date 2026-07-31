<?php

namespace App\Http\Requests\Admin\Inventory;

use App\Models\Inventory\InventorySubcategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateInventoryItemRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'uid' => strtoupper(trim((string) $this->input('uid'))),
            'unit' => strtoupper(trim((string) $this->input('unit', 'EA'))),
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $item = $this->route('inventoryItem');

        return [
            'uid' => ['required', 'string', 'max:255', Rule::unique('inventory_items', 'uid')->ignore($item)],
            'item_type' => ['required', Rule::in(['consumable', 'equipment'])],
            'inventory_category_id' => ['nullable', 'exists:inventory_categories,id'],
            'inventory_subcategory_id' => ['nullable', 'exists:inventory_subcategories,id'],
            'inventory_location_id' => ['nullable', 'exists:inventory_locations,id'],
            'type_category' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'size' => ['nullable', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:30'],
            'minimum_stock' => ['required', 'integer', 'min:0', 'max:9223372036854775807'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['required', 'boolean'],
            'current_stock' => ['prohibited'],
            'opening_stock' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled('inventory_subcategory_id')) {
                return;
            }
            $subcategory = InventorySubcategory::query()->find($this->integer('inventory_subcategory_id'));
            if (! $subcategory || $subcategory->inventory_category_id !== $this->integer('inventory_category_id')) {
                $validator->errors()->add('inventory_subcategory_id', 'Subkategori harus berasal dari kategori yang dipilih.');
            }
        }];
    }
}
