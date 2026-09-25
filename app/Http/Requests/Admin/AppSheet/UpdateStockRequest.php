<?php

namespace App\Http\Requests\Admin\AppSheet;

use App\Support\AppSheet\StockSheetMap;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $definition = StockSheetMap::definition((string) $this->route('stockKind'));
        $rules = [
            'identifier' => ['bail', 'required', 'string', 'max:255'],
        ];

        foreach ($definition['editable_fields'] as $requestField => $field) {
            $rules[$requestField] = $this->quantityRules();
            $rules[$field['original']] = $this->quantityRules();
        }

        return $rules;
    }

    /** @return list<mixed> */
    private function quantityRules(): array
    {
        return [
            'bail',
            'required',
            'numeric',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_numeric($value) || ! is_finite((float) $value)) {
                    $fail('Nilai stock harus berupa angka yang valid.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $messages = [
            'identifier.required' => 'Identifier item stock tidak tersedia.',
        ];

        $definition = StockSheetMap::definition((string) $this->route('stockKind'));
        foreach ($definition['editable_fields'] as $requestField => $field) {
            foreach ([$requestField, $field['original']] as $input) {
                $messages[$input.'.required'] = 'Nilai stock wajib diisi.';
                $messages[$input.'.numeric'] = 'Nilai stock harus berupa angka yang valid.';
            }
        }

        return $messages;
    }
}
