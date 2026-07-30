<?php

namespace App\Http\Requests\Admin\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('inventory_users', 'email')],
            'employee_number' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun Inventory lain.',
            'email.max' => 'Email maksimal 255 karakter.',
            'employee_number.max' => 'Nomor pegawai maksimal 255 karakter.',
            'phone.max' => 'Nomor telepon maksimal 30 karakter.',
            'position.max' => 'Jabatan maksimal 255 karakter.',
            'department.required' => 'Departemen wajib diisi.',
            'department.max' => 'Departemen maksimal 255 karakter.',
            'is_active.required' => 'Status akun wajib ditentukan.',
        ];
    }
}
