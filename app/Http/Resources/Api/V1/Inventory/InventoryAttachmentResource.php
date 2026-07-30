<?php

namespace App\Http\Resources\Api\V1\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attachment_type' => $this->attachment_type,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'url' => route('api.v1.inventory.attachments.show', $this->id),
        ];
    }
}
