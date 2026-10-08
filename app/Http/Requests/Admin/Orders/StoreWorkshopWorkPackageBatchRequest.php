<?php

namespace App\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkshopWorkPackageBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'packages' => ['required', 'array', 'min:2', 'max:99'],
            'packages.*.job_name' => ['required', 'string', 'max:255'],
            'packages.*.description' => ['nullable', 'string', 'max:5000'],
            'packages.*.target_date' => ['nullable', 'date'],
            'packages.*.assignments' => ['nullable', 'array', 'max:20'],
            // PIC uniqueness is scoped to each package and enforced by the service.
            'packages.*.assignments.*.pic_id' => ['nullable', 'integer'],
            'packages.*.assignments.*.descriptions' => ['nullable', 'array', 'max:20'],
            'packages.*.assignments.*.descriptions.*' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
