<?php

namespace App\Http\Requests\Admin\Hpp;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceDiropsSignedDocumentRequest extends FormRequest
{
    protected $errorBag = 'replaceDiropsDocument';

    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'signed_document' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'extensions:pdf', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'signed_document' => 'dokumen final DIROPS pengganti',
        ];
    }
}
