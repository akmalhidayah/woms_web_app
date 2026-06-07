<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLpjPplRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'selected_termin' => ['required', 'in:1,2'],
            'lpj_number' => ['nullable', 'string', 'max:255'],
            'ppl_number' => ['nullable', 'string', 'max:255'],
            'lpj_document' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'ppl_document' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove_lpj_document' => ['nullable', 'boolean'],
            'remove_ppl_document' => ['nullable', 'boolean'],
            'termin1_status' => ['required', 'in:belum,sudah'],
            'termin2_status' => ['required', 'in:belum,sudah'],
            'search' => ['nullable', 'string', 'max:255'],
            'po' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
