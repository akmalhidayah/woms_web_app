<?php

namespace App\Http\Requests\Admin;

use App\Models\BudgetVerification;
use App\Models\OutlineAgreement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOutlineAgreementMonthlyRealizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->normalizeRupiah($this->input('amount')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'realization_id' => ['nullable', 'integer', 'min:1'],
            'year' => ['required', 'integer', 'min:1', 'max:9999'],
            'month' => ['required', 'integer', 'between:1,12'],
            'kategori_biaya' => [
                'required',
                'string',
                Rule::in(array_keys(BudgetVerification::kategoriBiayaOptions())),
            ],
            'amount' => ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['year', 'month'])) {
                return;
            }

            $outlineAgreement = $this->route('outlineAgreement');

            if (! $outlineAgreement instanceof OutlineAgreement
                || ! $outlineAgreement->current_period_start
                || ! $outlineAgreement->current_period_end) {
                $validator->errors()->add('month', 'Periode aktif Outline Agreement tidak tersedia.');

                return;
            }

            $period = Carbon::create((int) $this->input('year'), (int) $this->input('month'), 1)->startOfMonth();
            $periodStart = $outlineAgreement->current_period_start->copy()->startOfMonth();
            $periodEnd = $outlineAgreement->current_period_end->copy()->startOfMonth();

            if ($period->lt($periodStart) || $period->gt($periodEnd)) {
                $validator->errors()->add(
                    'month',
                    'Periode realisasi harus berada dalam periode aktif Outline Agreement.'
                );
            }
        });
    }

    private function normalizeRupiah(mixed $value): mixed
    {
        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return $trimmed;
        }

        $normalized = preg_replace('/^Rp\.?\s*/i', '', $trimmed);

        if ($normalized === null || ! preg_match('/^-?[\d.,\s]+$/', $normalized)) {
            return $trimmed;
        }

        $digits = preg_replace('/\D/', '', $normalized);

        if ($digits === null || $digits === '') {
            return $trimmed;
        }

        return str_starts_with($normalized, '-') ? '-'.$digits : $digits;
    }
}
