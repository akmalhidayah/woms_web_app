<?php

namespace App\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class StoreOrderDocumentRequest extends FormRequest
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
        // Form memakai gambar_teknik_file[] (banyak file); satu file tunggal tetap diterima.
        $gambarTeknikRules = $this->file('gambar_teknik_file') instanceof UploadedFile
            ? ['gambar_teknik_file' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,pdf']]
            : [
                'gambar_teknik_file' => ['nullable', 'array', 'max:20'],
                'gambar_teknik_file.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,pdf'],
            ];

        return [
            'abnormalitas_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf'],
        ] + $gambarTeknikRules;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'abnormalitas_file.mimes' => 'Dokumen Abnormalitas wajib berupa file PDF.',
            'gambar_teknik_file.mimes' => 'Gambar Teknik wajib berupa file JPG, JPEG, atau PDF.',
            'gambar_teknik_file.*.mimes' => 'Gambar Teknik wajib berupa file JPG, JPEG, atau PDF.',
            'gambar_teknik_file.max' => 'Gambar Teknik maksimal 20 file per unggahan.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->hasFile('abnormalitas_file') && ! $this->hasFile('gambar_teknik_file')) {
                $validator->errors()->add('abnormalitas_file', 'Unggah minimal satu dokumen terlebih dahulu.');
            }
        });
    }
}
