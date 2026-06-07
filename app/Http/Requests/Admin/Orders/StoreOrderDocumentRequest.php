<?php

namespace App\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;
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
        return [
            'abnormalitas_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf'],
            'gambar_teknik_file' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,pdf,doc,docx'],
        ];
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
