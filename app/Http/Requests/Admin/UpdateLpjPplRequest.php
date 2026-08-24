<?php

namespace App\Http\Requests\Admin;

use App\Support\LpjPplIndexFilters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLpjPplRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'selected_termin' => ['required', 'integer', 'in:1,2'],
            'lpj_number' => ['nullable', 'string', 'max:255'],
            'ppl_number' => ['nullable', 'string', 'max:255'],
            'lpj_document' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'ppl_document' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove_lpj_document' => ['nullable', 'boolean'],
            'remove_ppl_document' => ['nullable', 'boolean'],
            'termin1_status' => ['required_if:selected_termin,1', 'nullable', 'in:belum,sudah'],
            'termin2_status' => ['required_if:selected_termin,2', 'nullable', 'in:belum,sudah'],
            'search' => ['nullable', 'string', 'max:255'],
            'po' => ['nullable', 'string', 'max:255'],
            'tab' => ['nullable', 'string', Rule::in(array_keys(app(LpjPplIndexFilters::class)->tabOptions()))],
            'stage' => ['nullable', 'string', Rule::in(array_keys(app(LpjPplIndexFilters::class)->stageOptions()))],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
