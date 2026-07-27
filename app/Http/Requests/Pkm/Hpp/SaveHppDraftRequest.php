<?php

namespace App\Http\Requests\Pkm\Hpp;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\User;
use App\Support\HppApprovalFlow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveHppDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user?->hasRole(User::ROLE_PKM) || $user?->isSuperAdmin());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'area_pekerjaan' => HppApprovalFlow::displayArea((string) $this->input('area_pekerjaan', '')),
        ]);
    }

    public function rules(): array
    {
        /** @var Hpp|null $hpp */
        $hpp = $this->route('hpp');

        return [
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id',
                Rule::unique('hpps', 'order_id')->ignore($hpp?->id),
                function (string $attribute, mixed $value, \Closure $fail) use ($hpp): void {
                    if ($hpp?->exists) {
                        if ((int) $value !== (int) $hpp->order_id) {
                            $fail('Order HPP draft tidak dapat diubah.');
                        }

                        return;
                    }

                    $order = Order::query()->with('scopeOfWork:id,order_id')->find($value);

                    if (! $order) {
                        return;
                    }

                    if (! in_array($order->catatan_status?->value, [
                        OrderUserNoteStatus::ApprovedJasa->value,
                        OrderUserNoteStatus::ApprovedWorkshopJasa->value,
                    ], true)) {
                        $fail('Order untuk HPP hanya bisa dipilih dari status Approved (Jasa) atau Approved (Workshop + Jasa).');
                    } elseif (! $order->scopeOfWork) {
                        $fail('Order belum memiliki Scope of Work.');
                    }
                },
            ],
            'outline_agreement_id' => ['required', 'integer', 'exists:outline_agreements,id'],
            'kategori_pekerjaan' => ['required', Rule::in(HppApprovalFlow::kategoriOptions())],
            'area_pekerjaan' => ['required', Rule::in(array_keys(HppApprovalFlow::areaOptions()))],
            'cost_centre' => ['nullable', 'string', 'max:255'],
            'hpp_updated_at' => [$hpp?->exists ? 'required' : 'nullable', 'string'],
            'jenis_label_visible' => ['nullable', 'array'],
            'jenis_label_visible.*' => ['nullable', 'string', 'max:255'],
            'sub_jenis_item' => ['nullable', 'array'],
            'kategori_item' => ['nullable', 'array'],
            'nama_item' => ['nullable', 'array'],
            'jumlah_item' => ['nullable', 'array'],
            'qty' => ['nullable', 'array'],
            'satuan' => ['nullable', 'array'],
            'harga_satuan' => ['nullable', 'array'],
            'harga_total' => ['nullable', 'array'],
            'keterangan' => ['nullable', 'array'],
            'approval_flow' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.unique' => 'Order ini sudah memiliki HPP. Silakan buka dan edit draft HPP yang sudah tersedia.',
        ];
    }
}
