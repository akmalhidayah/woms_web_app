<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\StoreOrderDocumentRequest;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Services\Orders\GambarTeknikPdfBuilder;
use App\Services\Orders\OrderDocumentService;
use App\Support\RecentApprovalSignatureResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class OrderDocumentController extends Controller
{
    public function __construct(
        private readonly OrderDocumentService $documentService,
        private readonly GambarTeknikPdfBuilder $gambarTeknikPdfBuilder,
    ) {}

    /**
     * Display the document list for an order.
     */
    public function index(Order $order): View
    {
        $order->load(['creator', 'documents.uploader', 'scopeOfWork.creator', 'orderWorkshop']);

        $documentMap = $order->documents
            ->filter(fn ($document) => in_array($document->jenis_dokumen->value, ['abnormalitas', 'gambar_teknik'], true))
            ->keyBy(fn ($document) => $document->jenis_dokumen->value);

        return view('admin.orders.show', [
            'order' => $order,
            'documentMap' => $documentMap,
            'scopeOfWork' => $order->scopeOfWork,
            'returnUrl' => $this->returnUrl($order),
            'recentSignatureDataUrl' => app(RecentApprovalSignatureResolver::class)
                ->latestFullSignatureForUser(request()->user()),
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

        // Tahap 1: siapkan semua file (termasuk penggabungan) sebelum menghapus dokumen lama,
        // supaya kegagalan gabung tidak menghilangkan dokumen yang sudah ada.
        $prepared = [];
        $temporaryPaths = [];

        try {
            foreach ($uploads as $field => $type) {
                if (! $request->hasFile($field)) {
                    continue;
                }

                $files = array_values(array_filter(Arr::wrap($request->file($field))));

                if (count($files) === 1) {
                    $prepared[$field] = $files[0];

                    continue;
                }

                $mergedContent = $this->gambarTeknikPdfBuilder->build(
                    $files,
                    'Gambar Teknik '.$order->nomor_order,
                );
                $temporaryPath = tempnam(sys_get_temp_dir(), 'woms-gambar-teknik-');

                if ($temporaryPath === false || file_put_contents($temporaryPath, $mergedContent) === false) {
                    throw new RuntimeException('Gagal menyimpan file gabungan Gambar Teknik.');
                }

                $temporaryPaths[] = $temporaryPath;
                $prepared[$field] = new UploadedFile(
                    $temporaryPath,
                    'gambar-teknik-'.Str::slug((string) $order->nomor_order).'.pdf',
                    'application/pdf',
                    null,
                    true,
                );
            }
        } catch (RuntimeException $exception) {
            $this->removeTemporaryFiles($temporaryPaths);

            return back()->withErrors(['gambar_teknik_file' => $exception->getMessage()]);
        }

        try {
            foreach ($prepared as $field => $file) {
                $type = $uploads[$field];

                $existing = $order->documents()
                    ->where('jenis_dokumen', $type->value)
                    ->get();

                foreach ($existing as $document) {
                    $this->documentService->delete($document);
                }

                $this->documentService->store(
                    order: $order,
                    file: $file,
                    type: $type,
                    user: $request->user(),
                );
            }
        } finally {
            $this->removeTemporaryFiles($temporaryPaths);
        }

        return redirect()
            ->to($this->documentPageUrl($order))
            ->with('status', 'Dokumen order berhasil diperbarui.');
    }

    /**
     * Preview the specified document inline in the browser.
     */
    public function preview(Order $order, OrderDocument $document)
    {
        abort_unless((int) $document->order_id === (int) $order->getKey(), 404);

        return $this->documentService->preview($document);
    }

    /**
     * Download the specified document.
     */
    public function download(Order $order, OrderDocument $document)
    {
        abort_unless((int) $document->order_id === (int) $order->getKey(), 404);

        return $this->documentService->download($document);
    }

    /**
     * Remove the specified document.
     */
    public function destroy(Order $order, OrderDocument $document): RedirectResponse
    {
        abort_unless((int) $document->order_id === (int) $order->getKey(), 404);

        $this->documentService->delete($document);

        return redirect()
            ->to($this->documentPageUrl($order))
            ->with('status', 'Dokumen order berhasil dihapus.');
    }

    /**
     * @param  list<string>  $paths
     */
    private function removeTemporaryFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function returnUrl(Order $order): string
    {
        if ($order->isWorkshopOrder()) {
            return route('admin.orders.workshop.index', ['search' => $order->nomor_order]);
        }

        return route('admin.orders.index');
    }

    private function documentPageUrl(Order $order): string
    {
        if ($order->loadMissing('orderWorkshop')->isWorkshopOrder()) {
            return route('admin.orders.documents.index', $order);
        }

        return route('admin.orders.show', $order);
    }
}
