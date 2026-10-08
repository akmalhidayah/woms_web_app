<?php

namespace App\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkshopWorkPackageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'job_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'target_date' => ['nullable', 'date'],
            'assignments' => ['nullable', 'array', 'max:20'],
            'assignments.*.pic_id' => ['nullable', 'integer', 'distinct'],
            'assignments.*.descriptions' => ['nullable', 'array', 'max:20'],
            'assignments.*.descriptions.*' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
