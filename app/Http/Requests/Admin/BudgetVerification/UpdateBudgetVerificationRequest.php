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
            'status_anggaran' => ['nullable', 'string', Rule::in(array_keys(BudgetVerification::statusAnggaranOptions()))],
            'kategori_item' => ['nullable', 'string', Rule::in(array_keys(BudgetVerification::kategoriItemOptions()))],
            'kategori_biaya' => ['nullable', 'string', Rule::in(array_keys(BudgetVerification::kategoriBiayaOptions()))],
            'cost_element' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
