<?php

namespace App\Http\Requests\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderUserNoteRequest extends FormRequest
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
        $status = (string) $this->input('catatan_status');
        $detailOptions = Order::userNoteDetailOptions()[$status] ?? null;

        return [
            'catatan_status' => ['required', Rule::in(OrderUserNoteStatus::values())],
            'catatan' => $detailOptions !== null
                ? ['nullable', 'string', Rule::in($detailOptions)]
                : ['nullable', 'string', 'max:1000'],
        ];
    }
}
