<?php

namespace App\Http\Requests\Admin;

use App\Models\OutlineAgreement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOutlineAgreementRequest extends FormRequest
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
            'nomor_oa' => ['required', 'string', 'max:255', 'unique:outline_agreements,nomor_oa'],
            'unit_work_id' => ['required', 'exists:unit_works,id'],
            'jenis_kontrak' => ['required', Rule::in(array_keys(OutlineAgreement::jenisKontrakOptions()))],
            'nama_kontrak' => ['required', 'string', 'max:255'],
            'nilai_kontrak_awal' => ['required', 'numeric', 'min:0'],
            'periode_awal_start' => ['required', 'date'],
            'periode_awal_end' => ['required', 'date', 'after_or_equal:periode_awal_start'],
            'target_years' => ['nullable', 'array'],
            'target_years.*' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'target_values' => ['nullable', 'array'],
            'target_values.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
