<?php

namespace App\Http\Requests\Admin\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryMasterDataRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => filled($this->input('code')) ? strtoupper(trim((string) $this->input('code'))) : null,
            'is_active' => $this->boolean('is_active'),
            'requires_damaged_photo' => $this->boolean('requires_damaged_photo'),
            'requires_new_item_photo' => $this->boolean('requires_new_item_photo'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $type = (string) $this->route('type');
        $id = $this->route('id');
        $table = match ($type) {
            'categories' => 'inventory_categories',
            'subcategories' => 'inventory_subcategories',
            'locations' => 'inventory_locations',
            'request-types' => 'inventory_request_types',
            default => 'inventory_categories',
        };

        $rules = [
            'code' => [$type === 'categories' || $type === 'subcategories' ? 'nullable' : 'required', 'string', 'max:255', Rule::unique($table, 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];

        if ($type === 'categories' || $type === 'request-types') {
            $rules['name'][] = Rule::unique($table, 'name')->ignore($id);
        }
        if ($type === 'subcategories') {
            $rules['inventory_category_id'] = ['required', 'integer', 'exists:inventory_categories,id'];
            $rules['name'][] = Rule::unique($table, 'name')->where('inventory_category_id', $this->integer('inventory_category_id'))->ignore($id);
        }
        if ($type === 'request-types') {
            $rules['requires_damaged_photo'] = ['required', 'boolean'];
            $rules['requires_new_item_photo'] = ['required', 'boolean'];
        }

        return $rules;
    }
}
