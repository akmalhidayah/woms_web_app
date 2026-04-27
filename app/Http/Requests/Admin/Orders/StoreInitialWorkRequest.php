<?php

namespace App\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StoreInitialWorkRequest extends FormRequest
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
            'outline_agreement_id' => ['required', 'integer', 'exists:outline_agreements,id'],
            'kepada_yth' => ['nullable', 'string', 'max:255'],
            'perihal' => ['required', 'string', 'max:255'],
            'tanggal_initial_work' => ['required', 'date'],
            'keterangan_pekerjaan' => ['nullable', 'string', 'max:3000'],
            'functional_location' => ['required', 'array', 'min:1'],
            'functional_location.*' => ['required', 'string', 'max:255'],
            'scope_pekerjaan' => ['required', 'array', 'min:1'],
            'scope_pekerjaan.*' => ['required', 'string', 'max:1000'],
            'qty' => ['required', 'array', 'min:1'],
            'qty.*' => ['required', 'string', 'max:100'],
            'stn' => ['required', 'array', 'min:1'],
            'stn.*' => ['required', 'string', 'max:50'],
            'keterangan' => ['nullable', 'array'],
            'keterangan.*' => ['nullable', 'string', 'max:500'],
            'initial_work_form_context' => ['nullable', 'string', 'in:create,edit'],
            'initial_work_order_key' => ['nullable', 'string'],
        ];
    }
}
