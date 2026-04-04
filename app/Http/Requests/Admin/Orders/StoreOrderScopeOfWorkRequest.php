<?php

namespace App\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderScopeOfWorkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_penginput' => ['required', 'string', 'max:255'],
            'tanggal_dokumen' => ['required', 'date'],
            'tanggal_pemakaian' => ['nullable', 'date'],
            'scope_pekerjaan' => ['required', 'array', 'min:1'],
            'scope_pekerjaan.*' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'array', 'min:1'],
            'qty.*' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'array', 'min:1'],
            'satuan.*' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'array'],
            'keterangan.*' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'tanda_tangan' => ['required', 'string'],
        ];
    }
}
