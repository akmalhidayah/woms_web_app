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
            'preparation_status' => ['sometimes', 'nullable', Rule::in(array_keys(OrderWorkshop::preparationOptions()))],
            'preparation_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'progress_status' => ['sometimes', 'nullable', Rule::in(array_keys(OrderWorkshop::progressOptions()))],
            'keterangan_progress' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'catatan' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
