<?php

namespace App\Http\Requests\Admin\Orders;

use App\Models\WorkshopWorkPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkshopWorkPackageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'job_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'target_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(array_keys(WorkshopWorkPackage::statusOptions()))],
            'pending_reason' => ['nullable', 'string', 'max:2000'],
            'assignments' => ['nullable', 'array', 'max:20'],
            'assignments.*.pic_id' => ['required', 'integer', 'distinct'],
            'assignments.*.descriptions' => ['required', 'array', 'min:1', 'max:20'],
            'assignments.*.descriptions.*' => ['required', 'string', 'max:1000'],
        ];
    }
}
