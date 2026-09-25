<?php

namespace App\Http\Requests\Admin\AppSheet;

use App\Services\AppSheet\GoogleSheetsWriter;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsumableTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'uid' => ['bail', 'required', 'string', 'max:255'],
            'input_type' => ['bail', 'required', Rule::in(GoogleSheetsWriter::transactionTypes())],
            'quantity' => [
                'bail',
                'required',
                'numeric',
                'gt:0',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_numeric($value) || ! is_finite((float) $value)) {
                        $fail('Jumlah transaksi harus berupa angka yang valid.');
                    }
                },
            ],
            'usage_purpose' => ['bail', 'nullable', 'required_if:input_type,STOCK OUT', 'string', 'max:1000'],
            'request_type' => ['bail', 'nullable', 'required_if:input_type,STOCK OUT', 'string', 'max:100'],
            'transaction_token' => ['bail', 'required', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'uid.required' => 'Pilih barang consumable terlebih dahulu.',
            'input_type.required' => 'Pilih jenis transaksi.',
            'input_type.in' => 'Jenis transaksi hanya boleh STOCK IN atau STOCK OUT.',
            'quantity.required' => 'Jumlah transaksi wajib diisi.',
            'quantity.numeric' => 'Jumlah transaksi harus berupa angka yang valid.',
            'quantity.gt' => 'Jumlah transaksi harus lebih besar dari 0.',
            'usage_purpose.required_if' => 'Tujuan penggunaan wajib diisi untuk STOCK OUT.',
            'request_type.required_if' => 'Jenis permintaan wajib dipilih untuk STOCK OUT.',
            'transaction_token.required' => 'Form transaksi sudah tidak valid. Silakan muat ulang halaman.',
            'transaction_token.uuid' => 'Form transaksi sudah tidak valid. Silakan muat ulang halaman.',
        ];
    }
}
