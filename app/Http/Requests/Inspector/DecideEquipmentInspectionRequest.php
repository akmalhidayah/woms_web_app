<?php

namespace App\Http\Requests\Inspector;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class DecideEquipmentInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_APPROVER;
    }

    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['approve', 'return'])], 'document_version' => ['required', 'integer', 'min:1'],
            'decision_note' => ['required_if:decision,return', 'nullable', 'string', 'max:2000'], 'confirmed' => ['accepted'],
            'signature_file' => ['required_if:decision,approve', 'nullable', 'file', 'image', 'mimes:png', 'mimetypes:image/png', 'max:1024', 'dimensions:max_width=2000,max_height=1000']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(back()->withErrors($validator)->withInput($this->only('decision_note')));
    }
}
