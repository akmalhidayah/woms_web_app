<?php

namespace App\Http\Requests\Pkm;

use App\Models\LhppBast;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLhppBastRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $approvalFlow = $this->input('approval_flow', []);

        $this->merge([
            'approval_flow' => is_array($approvalFlow)
                ? collect($approvalFlow)
                    ->map(fn (mixed $role): string => trim((string) $role))
                    ->filter()
                    ->values()
                    ->all()
                : [],
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validatesManualItems = $this->input('termin_type') !== 'termin_2'
            && $this->input('item_source', LhppBast::ITEM_SOURCE_MANUAL) === LhppBast::ITEM_SOURCE_MANUAL;

        return [
            'termin_type' => ['required', 'in:termin_1,termin_2'],
            'item_source' => ['nullable', Rule::in([
                LhppBast::ITEM_SOURCE_HPP_SNAPSHOT,
                LhppBast::ITEM_SOURCE_MANUAL,
            ])],
            'tanggal_bast' => ['required', 'date'],
            'nomor_order' => ['required', 'exists:orders,nomor_order'],
            'approval_threshold' => ['required', 'in:under_250,over_250'],
            'approval_flow' => ['nullable', 'array'],
            'approval_flow.*' => ['required', 'string', 'max:100'],
            'tipe_pekerjaan' => ['required', Rule::in(array_keys(LhppBast::tipePekerjaanOptions() + LhppBast::legacyTipePekerjaanOptions()))],
            'tanggal_mulai_pekerjaan' => ['nullable', 'date'],
            'tanggal_selesai_pekerjaan' => ['nullable', 'date', 'after_or_equal:tanggal_mulai_pekerjaan'],
            'material_rows' => $validatesManualItems ? ['nullable', 'array', 'max:100'] : ['nullable'],
            'material_rows.*.contract_item_id' => $validatesManualItems
                ? ['nullable', 'integer', 'exists:fabrication_construction_contracts,id']
                : ['nullable'],
            'material_rows.*.name' => $validatesManualItems ? ['nullable', 'string', 'max:255'] : ['nullable'],
            'material_rows.*.volume' => $validatesManualItems ? ['nullable', 'regex:/^\d{1,12}(?:\.\d{1,3})?$/'] : ['nullable'],
            'material_rows.*.unit' => $validatesManualItems ? ['nullable', 'string', 'max:20'] : ['nullable'],
            'material_rows.*.unit_price' => $validatesManualItems ? ['nullable', 'string', 'max:50'] : ['nullable'],
            'service_rows' => $validatesManualItems ? ['nullable', 'array', 'max:100'] : ['nullable'],
            'service_rows.*.contract_item_id' => $validatesManualItems
                ? ['nullable', 'integer', 'exists:fabrication_construction_contracts,id']
                : ['nullable'],
            'service_rows.*.name' => $validatesManualItems ? ['nullable', 'string', 'max:255'] : ['nullable'],
            'service_rows.*.volume' => $validatesManualItems ? ['nullable', 'regex:/^\d{1,12}(?:\.\d{1,3})?$/'] : ['nullable'],
            'service_rows.*.unit' => $validatesManualItems ? ['nullable', 'string', 'max:20'] : ['nullable'],
            'service_rows.*.unit_price' => $validatesManualItems ? ['nullable', 'string', 'max:50'] : ['nullable'],
            'gambar' => ['nullable', 'array'],
            'gambar.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'material_rows.*.volume.regex' => 'Volume material harus berupa angka positif dengan maksimal 3 angka desimal (gunakan titik).',
            'service_rows.*.volume.regex' => 'Volume jasa harus berupa angka positif dengan maksimal 3 angka desimal (gunakan titik).',
        ];
    }
}
