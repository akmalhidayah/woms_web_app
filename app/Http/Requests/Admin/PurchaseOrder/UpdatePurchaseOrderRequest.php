<?php

namespace App\Http\Requests\Admin\PurchaseOrder;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
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
            'purchase_order_number' => ['nullable', 'string', 'max:100'],
            'target_penyelesaian' => ['nullable', 'date'],
            'approval_target' => ['nullable', 'string', Rule::in(array_keys(PurchaseOrder::approvalTargetOptions()))],
            'approve_manager' => ['nullable', 'boolean'],
            'approve_senior_manager' => ['nullable', 'boolean'],
            'approve_general_manager' => ['nullable', 'boolean'],
            'approve_direktur_operasional' => ['nullable', 'boolean'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'po_document' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg', 'max:10240'],
        ];
    }
}
