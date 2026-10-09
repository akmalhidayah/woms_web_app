<?php

namespace App\Http\Requests\Inspector;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SaveAndSignEquipmentInspectionRequest extends SaveEquipmentInspectionRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'document_version' => ['required', 'integer', 'min:1'],
            'signature_data' => ['required', 'string', 'max:1400000'],
            'confirmed' => ['accepted'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        // Jangan menyimpan gambar base64 tanda tangan di session saat validasi gagal.
        throw new HttpResponseException(back()
            ->withErrors($validator)
            ->withInput($this->except(['signature_data', 'photos'])));
    }
}
