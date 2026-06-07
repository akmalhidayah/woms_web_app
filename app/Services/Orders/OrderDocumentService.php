<?php

namespace App\Services\Orders;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
        foreach ($this->candidateDisks() as $disk) {
            if ($disk->exists($document->path_file)) {
                $disk->delete($document->path_file);
            }
        }

        $document->delete();
    }

    /**
     * Preview an order document from current or legacy storage.
     */
    public function preview(OrderDocument $document): BinaryFileResponse
    {
        $disk = $this->diskContaining($document->path_file);

        abort_unless($disk, 404, 'File dokumen tidak ditemukan di storage.');

        return response()->file(
            $disk->path($document->path_file),
            [
                'Content-Type' => $disk->mimeType($document->path_file) ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$document->nama_file_asli.'"',
            ],
        );
    }

    public function absolutePath(OrderDocument $document): ?string
    {
        return $this->diskContaining($document->path_file)?->path($document->path_file);
    }

    public function mimeType(OrderDocument $document): ?string
    {
        return $this->diskContaining($document->path_file)?->mimeType($document->path_file);
    }

    /**
     * Download an order document.
     */
    public function download(OrderDocument $document): StreamedResponse
    {
        $disk = $this->diskContaining($document->path_file);

        abort_unless($disk, 404, 'File dokumen tidak ditemukan di storage.');

        return $disk->download(
            $document->path_file,
            $document->nama_file_asli,
        );
    }

    private function diskContaining(string $path): ?FilesystemAdapter
    {
        foreach ($this->candidateDisks() as $disk) {
            if ($disk->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    /**
     * @return list<FilesystemAdapter>
     */
    private function candidateDisks(): array
    {
        return [
            Storage::disk('local'),
            Storage::disk('public'),
            Storage::build([
                'driver' => 'local',
                'root' => storage_path('app'),
                'throw' => false,
            ]),
        ];
    }
}
