<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveEquipmentCiltRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN)
            && AdminMenuRegistry::canAccess($this->user(), AdminMenuRegistry::MENU_INSPEKSI);
    }

    public function rules(): array
    {
        return ['cilt_pdf' => ['required', 'file', 'max:10240', 'extensions:pdf', 'mimes:pdf', 'mimetypes:application/pdf']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('cilt_pdf')) {
                return;
            }

            $file = $this->file('cilt_pdf');
            if (! $file || file_get_contents($file->getRealPath(), false, null, 0, 5) !== '%PDF-') {
                $validator->errors()->add('cilt_pdf', 'Isi file harus berupa dokumen PDF yang valid.');
            }
        }];
    }

    public function attributes(): array
    {
        return ['cilt_pdf' => 'dokumen Standar CILT (PDF, maksimal 10 MB)'];
    }
}
