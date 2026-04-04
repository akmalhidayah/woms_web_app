<?php

namespace App\Http\Requests\Admin\Orders;

use App\Models\OrderWorkshop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderWorkshopRequest extends FormRequest
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
            'konfirmasi_anggaran' => ['sometimes', 'nullable', Rule::in(array_keys(OrderWorkshop::konfirmasiAnggaranOptions()))],
            'keterangan_konfirmasi' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status_anggaran' => ['sometimes', 'nullable', Rule::in(array_keys(OrderWorkshop::statusAnggaranOptions()))],
            'keterangan_anggaran' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status_material' => ['sometimes', 'nullable', Rule::in(array_keys(OrderWorkshop::materialOptions()))],
            'keterangan_material' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'progress_status' => ['sometimes', 'nullable', Rule::in(array_keys(OrderWorkshop::progressOptions()))],
            'keterangan_progress' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'catatan' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'nomor_e_korin' => ['sometimes', 'nullable', 'string', 'max:191'],
            'status_e_korin' => ['sometimes', 'nullable', Rule::in(array_keys(OrderWorkshop::eKorinStatusOptions()))],
        ];
    }
}
