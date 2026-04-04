<?php

namespace App\Http\Requests\Admin;

use App\Models\OutlineAgreement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOutlineAgreementAmendmentRequest extends FormRequest
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
        $type = $this->input('tipe_perubahan');

        return [
            'tipe_perubahan' => ['required', Rule::in(array_keys(OutlineAgreement::amendmentTypeOptions()))],
            'nilai_tambahan' => [
                Rule::requiredIf(in_array($type, [
                    OutlineAgreement::CHANGE_ADD_VALUE,
                    OutlineAgreement::CHANGE_EXTEND_AND_ADD_VALUE,
                ], true)),
                'nullable',
                'numeric',
                'min:0',
            ],
            'periode_end' => [
                Rule::requiredIf(in_array($type, [
                    OutlineAgreement::CHANGE_EXTEND,
                    OutlineAgreement::CHANGE_EXTEND_AND_ADD_VALUE,
                ], true)),
                'nullable',
                'date',
            ],
            'keterangan' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
