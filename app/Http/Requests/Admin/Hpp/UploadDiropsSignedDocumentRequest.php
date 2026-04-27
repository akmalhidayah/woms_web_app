<?php

namespace App\Http\Requests\Admin\Hpp;

use Illuminate\Foundation\Http\FormRequest;

class UploadDiropsSignedDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'signed_document' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'signed_document' => 'dokumen tanda tangan DIROPS',
        ];
    }
}
