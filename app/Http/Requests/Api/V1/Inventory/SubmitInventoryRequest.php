<?php

namespace App\Http\Requests\Api\V1\Inventory;

use App\Models\Inventory\InventoryRequestType;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_request_id' => ['required', 'uuid'],
            'inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'inventory_request_type_id' => ['required', 'integer', 'exists:inventory_request_types,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9223372036854775807'],
            'purpose' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'damaged_item_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'new_item_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'supporting_photos' => ['nullable', 'array', 'max:3'],
            'supporting_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'inventory_user_id' => ['prohibited'],
            'woms_user_id' => ['prohibited'],
            'stock_before' => ['prohibited'],
            'stock_after' => ['prohibited'],
            'current_stock' => ['prohibited'],
            'transaction_type' => ['prohibited'],
            'transaction_number' => ['prohibited'],
            'source' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $requestType = InventoryRequestType::query()->find($this->integer('inventory_request_type_id'));

                if (! $requestType?->is_active) {
                    $validator->errors()->add('inventory_request_type_id', 'Jenis permintaan tidak aktif.');

                    return;
                }

                $user = $this->user();
                $reference = 'MOBILE:'.strtolower($this->string('client_request_id')->toString());
                $isReplay = $user instanceof InventoryUser
                    && InventoryTransaction::query()
                        ->where('inventory_user_id', $user->getKey())
                        ->where('reference_number', $reference)
                        ->exists();

                if ($isReplay) {
                    return;
                }

                if ($requestType->requires_damaged_photo && ! $this->hasFile('damaged_item_photo')) {
                    $validator->errors()->add('damaged_item_photo', 'Foto alat rusak wajib diunggah.');
                }

                if ($requestType->requires_new_item_photo && ! $this->hasFile('new_item_photo')) {
                    $validator->errors()->add('new_item_photo', 'Foto alat baru wajib diunggah.');
                }
            },
        ];
    }
}
