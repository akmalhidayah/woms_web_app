<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveEquipmentInspectionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN)
            && AdminMenuRegistry::canAccess($this->user(), AdminMenuRegistry::MENU_INSPEKSI);
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');
        if (is_array($items)) {
            foreach ($items as &$item) {
                if (! is_array($item)) {
                    continue;
                }
                foreach (['label', 'group_name'] as $field) {
                    if (is_string($item[$field] ?? null)) {
                        $item[$field] = trim($item[$field]);
                    }
                }
            }
            unset($item);
        }
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'description' => is_string($this->input('description')) ? trim($this->input('description')) : $this->input('description'),
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'action' => ['required', Rule::in(['save_draft', 'publish'])],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'extensions:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*' => ['required', 'array:id,item_key,group_key,group_name,label'],
            'items.*.id' => ['nullable', 'integer', 'distinct'],
            'items.*.item_key' => ['nullable', 'string', 'max:191', 'distinct'],
            'items.*.group_key' => ['nullable', 'string', 'max:191'],
            'items.*.group_name' => ['nullable', 'string', 'max:150'],
            'items.*.label' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Form harus mempunyai minimal satu item pemeriksaan.',
            'items.min' => 'Form harus mempunyai minimal satu item pemeriksaan.',
            'items.*.label.required' => 'Nama setiap item pemeriksaan wajib diisi.',
            'image.max' => 'Ukuran foto peralatan maksimal 2 MB.',
        ];
    }
}
