<?php

namespace App\Http\Requests\Admin\Orders;

use App\Models\WorkshopWorkPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkshopWorkPackageStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_keys(WorkshopWorkPackage::statusOptions()))],
            'pending_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
