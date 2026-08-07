<?php

namespace App\Http\Requests\Admin;

use App\Models\BudgetVerification;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOutlineAgreementMonthlyRealizationRequest extends FormRequest
{
    private const NO_SECTION = 'Tidak ada seksi';

    private ?string $canonicalUnitWork = null;

    private ?string $canonicalSection = null;

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
            'unit_kerja' => trim((string) $this->input('unit_kerja')),
            'seksi' => trim((string) $this->input('seksi')),
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
            'unit_kerja' => ['required', 'string', 'max:255'],
            'seksi' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $validator->errors()->hasAny(['unit_kerja', 'seksi'])) {
                $this->validateStructurePair($validator);
            }

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

    /** @return array{unit_kerja: string, seksi: string} */
    public function structureSnapshot(): array
    {
        return [
            'unit_kerja' => $this->canonicalUnitWork ?? trim((string) $this->input('unit_kerja')),
            'seksi' => $this->canonicalSection ?? trim((string) $this->input('seksi')),
        ];
    }

    public function messages(): array
    {
        return [
            'unit_kerja.required' => 'Unit Kerja wajib dipilih.',
            'seksi.required' => 'Seksi wajib dipilih.',
        ];
    }

    private function validateStructurePair(Validator $validator): void
    {
        $unit = UnitWork::query()
            ->with('sections:id,unit_work_id,name')
            ->where('name', trim((string) $this->input('unit_kerja')))
            ->first();

        if (! $unit) {
            $validator->errors()->add(
                'unit_kerja',
                'Unit Kerja yang dipilih tidak terdaftar pada Struktur Organisasi.',
            );

            return;
        }

        $section = trim((string) $this->input('seksi'));
        $this->canonicalUnitWork = $unit->name;

        if ($unit->sections->isEmpty()) {
            if ($section !== self::NO_SECTION) {
                $validator->errors()->add(
                    'seksi',
                    'Unit Kerja ini tidak memiliki seksi. Gunakan pilihan "Tidak ada seksi".',
                );

                return;
            }

            $this->canonicalSection = self::NO_SECTION;

            return;
        }

        $canonicalSection = $unit->sections->firstWhere('name', $section)?->name;

        if ($canonicalSection === null) {
            $validator->errors()->add(
                'seksi',
                'Seksi yang dipilih tidak terdaftar pada Unit Kerja tersebut.',
            );

            return;
        }

        $this->canonicalSection = $canonicalSection;
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
