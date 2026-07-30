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
            'quantity' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,3})?$/', 'not_in:0,0.0,0.00,0.000'],
            'transaction_at' => ['required', 'date'],
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
            'quantity.regex' => 'Jumlah harus berupa angka positif dengan maksimal tiga angka desimal.',
            'quantity.not_in' => 'Jumlah stok masuk harus lebih besar dari 0.',
            'transaction_at.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_at.date' => 'Tanggal transaksi tidak valid.',
            'reference_number.max' => 'Nomor referensi maksimal 100 karakter.',
            'notes.max' => 'Catatan maksimal 2000 karakter.',
        ];
    }
}
