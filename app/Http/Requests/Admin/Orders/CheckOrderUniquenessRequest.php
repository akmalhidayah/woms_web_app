<?php

namespace App\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckOrderUniquenessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'field' => ['required', Rule::in(['nomor_order', 'notifikasi'])],
            'value' => ['required', 'string', 'max:255'],
            // Nomor order milik order yang sedang diedit, agar tidak dianggap duplikat dirinya sendiri.
            'ignore' => ['nullable', 'string', 'max:100'],
        ];
    }
}
