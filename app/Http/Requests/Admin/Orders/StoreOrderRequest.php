<?php

namespace App\Http\Requests\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
            'nomor_order' => ['required', 'string', 'max:100', 'unique:orders,nomor_order'],
            'notifikasi' => ['nullable', 'string', 'max:255', 'unique:orders,notifikasi'],
            'nama_pekerjaan' => ['required', 'string', 'max:255'],
            'unit_kerja' => ['required', 'string', 'max:255'],
            'seksi' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'prioritas' => ['required', Rule::in(array_keys(Order::priorityOptions()))],
            'tanggal_order' => ['required', 'date'],
            'target_selesai' => ['required', 'date', 'after_or_equal:tanggal_order'],
            'catatan_status' => ['required', Rule::in(array_keys(OrderUserNoteStatus::options()))],
            'catatan' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_order.required' => 'Nomor order wajib diisi.',
            'nomor_order.unique' => 'Nomor order ini sudah digunakan.',
            'notifikasi.unique' => 'Nomor notifikasi ini sudah digunakan.',
            'target_selesai.after_or_equal' => 'Target selesai tidak boleh lebih awal dari tanggal order.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nomor_order' => 'nomor order',
            'notifikasi' => 'nomor notifikasi',
            'nama_pekerjaan' => 'nama pekerjaan',
            'unit_kerja' => 'unit kerja',
            'target_selesai' => 'target selesai',
            'catatan_status' => 'status catatan',
        ];
    }
}
