<?php

namespace App\Http\Requests\Admin\Hpp;

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
        return [
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'outline_agreement_id' => ['required', 'integer', 'exists:outline_agreements,id'],
            'kategori_pekerjaan' => ['required', Rule::in(HppApprovalFlow::kategoriOptions())],
            'area_pekerjaan' => ['required', Rule::in(array_keys(HppApprovalFlow::areaOptions()))],
            'nilai_hpp_bucket' => ['required', Rule::in(array_keys(HppApprovalFlow::bucketOptions()))],
            'cost_centre' => ['nullable', 'string', 'max:255'],
            'jenis_label_visible' => ['nullable', 'array'],
            'jenis_label_visible.*' => ['nullable', 'string', 'max:255'],
            'nama_item' => ['nullable', 'array'],
            'jumlah_item' => ['nullable', 'array'],
            'qty' => ['nullable', 'array'],
            'satuan' => ['nullable', 'array'],
            'harga_satuan' => ['nullable', 'array'],
            'harga_total' => ['nullable', 'array'],
            'keterangan' => ['nullable', 'array'],
        ];
    }
}
