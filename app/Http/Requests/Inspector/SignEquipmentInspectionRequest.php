<?php

namespace App\Http\Requests\Inspector;

use App\Models\EquipmentInspection;
use App\Policies\EquipmentInspectionPolicy;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SignEquipmentInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $inspection = $this->route('inspection');

        // An authorized retry of a completed signature remains idempotent.
        return $inspection instanceof EquipmentInspection && $this->user()
            && (new EquipmentInspectionPolicy)->view($this->user(), $inspection);
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:1'],
            'document_version' => ['required', 'integer', 'min:1'],
            'signature_data' => ['required', 'string', 'max:1400000'],
            'confirmed' => ['accepted'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        // Never flash a base64 signature back into the session.
        throw new HttpResponseException(redirect()->route('inspector.inspections.show', $this->route('inspection'))
            ->withErrors($validator));
    }
}
