<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFabricationConstructionContractRequest extends FormRequest
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
            'tahun' => ['required', 'integer', 'digits:4', 'min:2000', 'max:2100'],
            'jenis_item' => ['required', 'string', 'max:255'],
            'sub_jenis_item' => ['nullable', 'string', 'max:255'],
            'kategori_item' => ['nullable', 'string', 'max:255'],
            'nama_item' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:50'],
            'harga_satuan' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tahun.required' => 'Tahun wajib diisi.',
            'jenis_item.required' => 'Jenis item wajib diisi.',
            'nama_item.required' => 'Nama item wajib diisi.',
            'satuan.required' => 'Satuan wajib diisi.',
            'harga_satuan.required' => 'Harga satuan wajib diisi.',
            'harga_satuan.numeric' => 'Harga satuan harus berupa angka.',
        ];
    }
}
