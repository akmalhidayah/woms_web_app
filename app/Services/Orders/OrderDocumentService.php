<?php

namespace App\Services\Orders;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderDocumentService
{
    /**
     * Store a new order document.
     */
    public function store(Order $order, UploadedFile $file, OrderDocumentType $type, ?User $user = null): OrderDocument
    {
        $path = $file->store(
            path: "orders/{$order->id}/documents/{$type->value}",
            options: 'local',
        );

        return $order->documents()->create([
            'jenis_dokumen' => $type,
            'nama_file_asli' => $file->getClientOriginalName(),
            'path_file' => $path,
            'uploaded_by' => $user?->id,
            'uploaded_at' => now(),
        ]);
    }

    /**
     * Delete an order document and its file.
     */
    public function delete(OrderDocument $document): void
    {
        Storage::disk('local')->delete($document->path_file);
        $document->delete();
    }

    /**
     * Download an order document.
     */
    public function download(OrderDocument $document): StreamedResponse
    {
        return Storage::disk('local')->download(
            $document->path_file,
            $document->nama_file_asli,
        );
    }
}
