<?php

namespace App\Http\Requests\Pkm;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class GlobalSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->hasRole(User::ROLE_PKM) || $user->isSuperAdmin());
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Kata pencarian wajib diisi.',
            'q.min' => 'Masukkan minimal 2 karakter.',
            'q.max' => 'Kata pencarian maksimal 100 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => preg_replace('/\s+/u', ' ', trim((string) $this->input('q'))),
        ]);
    }
}
