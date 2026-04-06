<?php

namespace App\Http\Requests\Pkm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobWaitingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_progress' => ['nullable', 'boolean'],
            'progress_pekerjaan' => ['nullable', 'integer', 'min:0', 'max:100'],
            'target_penyelesaian' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            '_filter_priority' => ['nullable', 'string', 'max:50'],
            '_filter_search' => ['nullable', 'string', 'max:255'],
            '_filter_page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
