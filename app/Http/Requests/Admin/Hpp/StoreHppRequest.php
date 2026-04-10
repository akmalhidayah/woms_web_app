<?php

namespace App\Http\Requests\Admin\Hpp;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Hpp;
use App\Models\Order;
use App\Support\HppApprovalFlow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHppRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'action' => (string) $this->input('action', 'draft'),
            'area_pekerjaan' => HppApprovalFlow::displayArea((string) $this->input('area_pekerjaan', '')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Hpp|null $hpp */
        $hpp = $this->route('hpp');

        return [
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id',
                Rule::unique('hpps', 'order_id')->ignore($hpp?->id),
                function (string $attribute, mixed $value, \Closure $fail) use ($hpp): void {
                    if ($hpp?->exists) {
                        return;
                    }

                    $order = Order::query()
                        ->with(['documents:id,order_id,jenis_dokumen', 'scopeOfWork:id,order_id'])
                        ->find($value);

                    if (! $order) {
                        return;
                    }

                    if (! in_array($order->catatan_status?->value, [
                        OrderUserNoteStatus::ApprovedJasa->value,
                        OrderUserNoteStatus::ApprovedWorkshopJasa->value,
                    ], true)) {
                        $fail('Order untuk HPP hanya bisa dipilih dari status Approved (Jasa) atau Approved (Workshop + Jasa).');

                        return;
                    }

                    $documentTypes = $order->documents
                        ->pluck('jenis_dokumen')
                        ->map(fn ($type) => $type instanceof OrderDocumentType ? $type->value : (string) $type)
                        ->all();

                    if (! in_array(OrderDocumentType::Abnormalitas->value, $documentTypes, true)) {
                        $fail('Order belum memiliki dokumen Abnormalitas.');
                    }

                    if (! in_array(OrderDocumentType::GambarTeknik->value, $documentTypes, true)) {
                        $fail('Order belum memiliki dokumen Gambar Teknik.');
                    }

                    if (! $order->scopeOfWork) {
                        $fail('Order belum memiliki Scope of Work.');
                    }
                },
            ],
            'outline_agreement_id' => ['required', 'integer', 'exists:outline_agreements,id'],
            'kategori_pekerjaan' => ['required', Rule::in(HppApprovalFlow::kategoriOptions())],
            'area_pekerjaan' => ['required', Rule::in(array_keys(HppApprovalFlow::areaOptions()))],
            'nilai_hpp_bucket' => ['required', Rule::in(array_keys(HppApprovalFlow::bucketOptions()))],
            'cost_centre' => ['nullable', 'string', 'max:255'],
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
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.unique' => 'Order ini sudah dibuatkan HPP. Hapus HPP lama terlebih dahulu jika ingin membuat ulang.',
        ];
    }
}
