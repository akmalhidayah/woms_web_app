<?php

namespace App\Http\Requests\Admin\Orders;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
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
        /** @var \App\Models\Order $order */
        $order = $this->route('order');

        return [
            'nomor_order' => ['required', 'string', 'max:100', Rule::unique('orders', 'nomor_order')->ignore($order->id)],
            'nama_pekerjaan' => ['required', 'string', 'max:255'],
            'unit_kerja' => ['required', 'string', 'max:255'],
            'seksi' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'prioritas' => ['required', Rule::in(array_keys(Order::priorityOptions()))],
            'tanggal_order' => ['required', 'date'],
            'target_selesai' => ['required', 'date', 'after_or_equal:tanggal_order'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
