<?php

namespace App\Http\Requests\Pkm;

use Illuminate\Foundation\Http\FormRequest;

class StoreLhppBastRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'termin_type' => ['required', 'in:termin_1,termin_2'],
            'tanggal_bast' => ['required', 'date'],
            'nomor_order' => ['required', 'exists:orders,nomor_order'],
            'approval_threshold' => ['required', 'in:under_250,over_250'],
            'tanggal_mulai_pekerjaan' => ['nullable', 'date'],
            'tanggal_selesai_pekerjaan' => ['nullable', 'date', 'after_or_equal:tanggal_mulai_pekerjaan'],
            'material_rows' => ['nullable', 'array'],
            'material_rows.*.name' => ['nullable', 'string', 'max:255'],
            'material_rows.*.volume' => ['nullable', 'string', 'max:50'],
            'material_rows.*.unit' => ['nullable', 'string', 'max:20'],
            'material_rows.*.unit_price' => ['nullable', 'string', 'max:50'],
            'service_rows' => ['nullable', 'array'],
            'service_rows.*.name' => ['nullable', 'string', 'max:255'],
            'service_rows.*.volume' => ['nullable', 'string', 'max:50'],
            'service_rows.*.unit' => ['nullable', 'string', 'max:20'],
            'service_rows.*.unit_price' => ['nullable', 'string', 'max:50'],
        ];
    }
}
