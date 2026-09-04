<?php

namespace App\Http\Requests\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\UnitWork;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    private const NO_SECTION = 'Tidak ada seksi';

    private const MAX_BIAYA = 9999999999999999;

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
            'nomor_order' => ['required', 'string', 'max:100', 'unique:orders,nomor_order'],
            'notifikasi' => ['nullable', 'string', 'max:255', 'unique:orders,notifikasi'],
            'nama_pekerjaan' => ['required', 'string', 'max:255'],
            'unit_kerja' => ['required', 'string', 'max:255'],
            'seksi' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'prioritas' => ['required', Rule::in(array_keys(Order::priorityOptions()))],
            'tanggal_order' => ['required', 'date'],
            'target_selesai' => ['required', 'date', 'after_or_equal:tanggal_order'],
            'biaya' => $this->routeIs('admin.orders.workshop.store')
                ? ['nullable', 'integer', 'min:0', 'max:'.self::MAX_BIAYA]
                : ['prohibited'],
            'catatan_status' => ['required', Rule::in(array_keys(OrderUserNoteStatus::options()))],
            'catatan' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'unit_kerja' => trim((string) $this->input('unit_kerja')),
            'seksi' => $this->normalizeSection($this->input('seksi')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['unit_kerja', 'seksi'])) {
                return;
            }

            $this->validateStructurePair($validator);
        });
    }

    public function messages(): array
    {
        return [
            'nomor_order.required' => 'Nomor order wajib diisi.',
            'nomor_order.unique' => 'Nomor order ini sudah digunakan.',
            'notifikasi.unique' => 'Nomor notifikasi ini sudah digunakan.',
            'target_selesai.after_or_equal' => 'Target selesai tidak boleh lebih awal dari tanggal order.',
            'unit_kerja.required' => 'Unit Kerja wajib dipilih.',
            'seksi.required' => 'Seksi wajib dipilih.',
            'biaya.integer' => 'Biaya harus berupa nominal Rupiah tanpa pecahan.',
            'biaya.min' => 'Biaya tidak boleh bernilai negatif.',
            'biaya.max' => 'Biaya melebihi batas nominal yang dapat disimpan.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nomor_order' => 'nomor order',
            'notifikasi' => 'nomor notifikasi',
            'nama_pekerjaan' => 'nama pekerjaan',
            'unit_kerja' => 'unit kerja',
            'target_selesai' => 'target selesai',
            'biaya' => 'biaya',
            'catatan_status' => 'status catatan',
        ];
    }

    private function validateStructurePair(Validator $validator): void
    {
        $unit = UnitWork::query()
            ->with('sections:id,unit_work_id,name')
            ->where('name', $this->string('unit_kerja')->toString())
            ->first();

        if (! $unit) {
            $validator->errors()->add(
                'unit_kerja',
                'Unit Kerja yang dipilih tidak terdaftar pada Struktur Organisasi.'
            );

            return;
        }

        $section = $this->string('seksi')->toString();

        if ($unit->sections->isEmpty()) {
            if ($section !== self::NO_SECTION) {
                $validator->errors()->add(
                    'seksi',
                    'Unit Kerja ini tidak memiliki seksi. Gunakan pilihan "Tidak ada seksi".'
                );
            }

            return;
        }

        if (! $unit->sections->contains('name', $section)) {
            $validator->errors()->add(
                'seksi',
                'Seksi yang dipilih tidak terdaftar pada Unit Kerja tersebut.'
            );
        }
    }

    private function normalizeSection(mixed $section): string
    {
        $normalized = trim((string) $section);

        return $normalized === 'General' ? self::NO_SECTION : $normalized;
    }
}
