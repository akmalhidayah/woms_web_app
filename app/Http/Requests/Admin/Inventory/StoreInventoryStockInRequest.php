<?php

namespace App\Http\Requests\Admin\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9223372036854775807'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_item_id.required' => 'Barang wajib dipilih.',
            'inventory_item_id.exists' => 'Barang yang dipilih tidak tersedia.',
            'quantity.required' => 'Jumlah stok masuk wajib diisi.',
            'quantity.integer' => 'Jumlah stok harus berupa bilangan bulat positif.',
            'reference_number.max' => 'Nomor referensi maksimal 100 karakter.',
            'notes.max' => 'Catatan maksimal 2000 karakter.',
        ];
    }
}
