<?php

namespace App\Http\Requests\Admin\Orders;

class UpdateWorkshopWorkPackageRequest extends StoreWorkshopWorkPackageRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'target_date' => ['required', 'date']];
    }
}
