<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryTransactionAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    public function __invoke(
        Request $request,
        InventoryTransactionAttachment $inventoryAttachment
    ): Response {
        $inventoryAttachment->load('transaction');
        abort_unless(
            (int) $inventoryAttachment->transaction?->inventory_user_id === (int) $request->user()?->getKey(),
            404
        );
        abort_if($this->unsafePath($inventoryAttachment->path), 404);

        $disk = Storage::disk($inventoryAttachment->disk);
        abort_unless($disk->exists($inventoryAttachment->path), 404);
        $name = basename(str_replace('\\', '/', $inventoryAttachment->original_name ?: 'attachment'));
        $name = preg_replace('/[[:cntrl:]]/', '', $name) ?: 'attachment';
        $name = Str::limit($name, 200, '');

        return $disk->response($inventoryAttachment->path, $name, [
            'Content-Type' => $inventoryAttachment->mime_type
                ?: $disk->mimeType($inventoryAttachment->path)
                ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ], 'inline');
    }

    private function unsafePath(string $path): bool
    {
        return str_starts_with($path, '/') || str_contains($path, '..') || str_contains($path, "\0");
    }
}
