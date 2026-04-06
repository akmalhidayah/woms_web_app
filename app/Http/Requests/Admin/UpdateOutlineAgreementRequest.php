<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOutlineAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\OutlineAgreement|null $agreement */
        $agreement = $this->route('outlineAgreement');

        return [
            'nomor_oa' => [
                'required',
                'string',
                'max:255',
                Rule::unique('outline_agreements', 'nomor_oa')->ignore($agreement?->id),
            ],
            'unit_work_id' => ['required', 'exists:unit_works,id'],
            'jenis_kontrak' => [
                'required',
                'string',
                'max:255',
                Rule::exists('unit_work_sections', 'name')
                    ->where(fn ($query) => $query->where('unit_work_id', (int) $this->input('unit_work_id'))),
            ],
            'nama_kontrak' => ['required', 'string', 'max:255'],
            'current_total_nilai' => ['required', 'numeric', 'min:0'],
            'current_period_end' => ['required', 'date', 'after_or_equal:current_period_start'],
            'current_period_start' => ['nullable', 'date'],
            'keterangan_perubahan' => ['nullable', 'string'],
            'target_years' => ['nullable', 'array'],
            'target_years.*' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'target_values' => ['nullable', 'array'],
            'target_values.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
