<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryTransactionAttachment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function __invoke(InventoryTransactionAttachment $inventoryAttachment): Response
    {
        $attachment = $inventoryAttachment->load('transaction');
        abort_unless($attachment->transaction, 404);
        abort_if(str_starts_with($attachment->path, '/') || str_contains($attachment->path, '..') || str_contains($attachment->path, "\0"), 404);
        abort_unless(config("filesystems.disks.{$attachment->disk}") !== null, 404);
        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);
        $name = preg_replace('/[[:cntrl:]]/', '', basename((string) ($attachment->original_name ?: 'attachment'))) ?: 'attachment';

        return response($disk->get($attachment->path), 200, [
            'Content-Type' => $attachment->mime_type ?: $disk->mimeType($attachment->path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($name).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
