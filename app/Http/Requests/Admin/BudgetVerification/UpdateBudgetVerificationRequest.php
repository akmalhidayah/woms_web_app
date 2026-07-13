<?php

namespace App\Http\Requests\Admin\BudgetVerification;

use App\Models\BudgetVerification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_anggaran' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(BudgetVerification::statusAnggaranOptions()))],
            'kategori_item' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(BudgetVerification::kategoriItemOptions()))],
            'kategori_biaya' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(BudgetVerification::kategoriBiayaOptions()))],
            'cost_element' => ['sometimes', 'nullable', 'string', 'max:50'],
            'catatan' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
