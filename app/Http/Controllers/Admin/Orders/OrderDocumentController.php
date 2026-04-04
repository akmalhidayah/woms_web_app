<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\StoreOrderDocumentRequest;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Services\Orders\OrderDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrderDocumentController extends Controller
{
    public function __construct(
        private readonly OrderDocumentService $documentService,
    ) {
    }

    /**
     * Display the document list for an order.
     */
    public function index(Order $order): View
    {
        $order->load(['creator', 'documents.uploader', 'scopeOfWork.creator']);

        $documentMap = $order->documents
            ->filter(fn ($document) => in_array($document->jenis_dokumen->value, ['abnormalitas', 'gambar_teknik'], true))
            ->keyBy(fn ($document) => $document->jenis_dokumen->value);

        return view('admin.orders.show', [
            'order' => $order,
            'documentMap' => $documentMap,
            'scopeOfWork' => $order->scopeOfWork,
        ]);
    }

    /**
     * Store an uploaded document.
     */
    public function store(StoreOrderDocumentRequest $request, Order $order): RedirectResponse
    {
        $request->validated();

        $uploads = [
            'abnormalitas_file' => OrderDocumentType::Abnormalitas,
            'gambar_teknik_file' => OrderDocumentType::GambarTeknik,
        ];

        foreach ($uploads as $field => $type) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $existing = $order->documents()
                ->where('jenis_dokumen', $type->value)
                ->get();

            foreach ($existing as $document) {
                $this->documentService->delete($document);
            }

            $this->documentService->store(
                order: $order,
                file: $request->file($field),
                type: $type,
                user: $request->user(),
            );
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Dokumen order berhasil diperbarui.');
    }

    /**
     * Preview the specified document inline in the browser.
     */
    public function preview(Order $order, OrderDocument $document)
    {
        abort_unless($document->order_id === $order->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path_file), 404);

        return response()->file(
            Storage::disk('local')->path($document->path_file),
            [
                'Content-Type' => Storage::disk('local')->mimeType($document->path_file) ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$document->nama_file_asli.'"',
            ],
        );
    }

    /**
     * Download the specified document.
     */
    public function download(Order $order, OrderDocument $document)
    {
        abort_unless($document->order_id === $order->id, 404);

        return $this->documentService->download($document);
    }

    /**
     * Remove the specified document.
     */
    public function destroy(Order $order, OrderDocument $document): RedirectResponse
    {
        abort_unless($document->order_id === $order->id, 404);

        $this->documentService->delete($document);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Dokumen order berhasil dihapus.');
    }
}
