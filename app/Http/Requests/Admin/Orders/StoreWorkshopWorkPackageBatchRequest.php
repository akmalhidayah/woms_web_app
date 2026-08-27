<?php

namespace App\Http\Requests\Admin\Orders;

use App\Models\WorkshopWorkPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkshopWorkPackageBatchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'packages' => ['required', 'array', 'min:2', 'max:99'],
            'packages.*.job_name' => ['required', 'string', 'max:255'],
            'packages.*.description' => ['nullable', 'string', 'max:5000'],
            'packages.*.target_date' => ['nullable', 'date'],
            'packages.*.status' => ['nullable', Rule::in(array_keys(WorkshopWorkPackage::statusOptions()))],
            'packages.*.pending_reason' => ['nullable', 'string', 'max:2000'],
            'packages.*.assignments' => ['nullable', 'array', 'max:20'],
            'packages.*.assignments.*.pic_id' => ['required', 'integer', 'distinct'],
            'packages.*.assignments.*.descriptions' => ['required', 'array', 'min:1', 'max:20'],
            'packages.*.assignments.*.descriptions.*' => ['required', 'string', 'max:1000'],
        ];
    }
}
