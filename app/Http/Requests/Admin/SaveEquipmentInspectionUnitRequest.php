<?php

namespace App\Http\Requests\Admin;

use App\Models\EquipmentInspectionUnit;
use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveEquipmentInspectionUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN)
            && AdminMenuRegistry::canAccess($this->user(), AdminMenuRegistry::MENU_INSPEKSI);
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['asset_code', 'name', 'location', 'description'] as $field) {
            if (is_string($this->input($field))) {
                $values[$field] = trim($this->input($field));
            }
        }
        $values['public_enabled'] = $this->boolean('public_enabled');
        $values['remove_image'] = $this->boolean('remove_image');
        $this->merge($values);
    }

    public function rules(): array
    {
        $unit = $this->route('equipmentInspectionUnit');

        return [
            'equipment_inspection_form_id' => ['required', 'integer', Rule::exists('equipment_inspection_forms', 'id')],
            'asset_code' => ['required', 'string', 'max:100', Rule::unique('equipment_inspection_units', 'asset_code')->ignore($unit?->id)],
            'name' => ['required', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lifecycle_status' => ['required', Rule::in(EquipmentInspectionUnit::STATUSES)],
            'public_enabled' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'extensions:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'remove_image' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_code.unique' => 'Kode unit/aset sudah digunakan oleh unit lain.',
            'image.max' => 'Ukuran foto unit maksimal 2 MB.',
        ];
    }
}
