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
        return [
            'termin_type' => ['required', 'in:termin_1,termin_2'],
            'tanggal_bast' => ['required', 'date'],
            'nomor_order' => ['required', 'exists:orders,nomor_order'],
            'approval_threshold' => ['required', 'in:under_250,over_250'],
            'approval_flow' => ['nullable', 'array'],
            'approval_flow.*' => ['required', 'string', 'max:100'],
            'tipe_pekerjaan' => ['required', Rule::in(array_keys(LhppBast::tipePekerjaanOptions() + LhppBast::legacyTipePekerjaanOptions()))],
            'tanggal_mulai_pekerjaan' => ['nullable', 'date'],
            'tanggal_selesai_pekerjaan' => ['nullable', 'date', 'after_or_equal:tanggal_mulai_pekerjaan'],
            'material_rows' => ['nullable', 'array', 'max:100'],
            'material_rows.*.contract_item_id' => ['nullable', 'integer', 'exists:fabrication_construction_contracts,id'],
            'material_rows.*.name' => ['nullable', 'string', 'max:255'],
            'material_rows.*.volume' => ['nullable', 'regex:/^\d{1,12}(?:\.\d{1,3})?$/'],
            'material_rows.*.unit' => ['nullable', 'string', 'max:20'],
            'material_rows.*.unit_price' => ['nullable', 'string', 'max:50'],
            'service_rows' => ['nullable', 'array', 'max:100'],
            'service_rows.*.contract_item_id' => ['nullable', 'integer', 'exists:fabrication_construction_contracts,id'],
            'service_rows.*.name' => ['nullable', 'string', 'max:255'],
            'service_rows.*.volume' => ['nullable', 'regex:/^\d{1,12}(?:\.\d{1,3})?$/'],
            'service_rows.*.unit' => ['nullable', 'string', 'max:20'],
            'service_rows.*.unit_price' => ['nullable', 'string', 'max:50'],
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
