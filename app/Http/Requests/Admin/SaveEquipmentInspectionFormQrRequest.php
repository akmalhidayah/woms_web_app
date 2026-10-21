<?php

namespace App\Http\Requests\Admin;

use App\Models\EquipmentInspectionForm;
use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveEquipmentInspectionFormQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN)
            && AdminMenuRegistry::canAccess($this->user(), AdminMenuRegistry::MENU_INSPEKSI);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'asset_code' => is_string($this->input('asset_code'))
                ? trim($this->input('asset_code'))
                : $this->input('asset_code'),
        ]);
    }

    public function rules(): array
    {
        $form = $this->route('equipmentInspectionForm');
        $unitIds = $form instanceof EquipmentInspectionForm
            ? $form->units()->limit(2)->pluck('id')
            : collect();
        $unitId = $unitIds->count() === 1 ? $unitIds->first() : null;

        return [
            'asset_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('equipment_inspection_units', 'asset_code')->ignore($unitId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_code.required' => 'Kode alat wajib diisi.',
            'asset_code.unique' => 'Kode alat sudah digunakan oleh peralatan lain.',
        ];
    }
}
