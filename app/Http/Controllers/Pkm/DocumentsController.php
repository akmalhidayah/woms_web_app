<?php

namespace App\Http\Controllers\Pkm;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Http\Controllers\Controller;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\Order;
use App\Services\Orders\OrderDocumentService;
use App\Support\PdfMergeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class DocumentsController extends Controller
{
    public function __construct(
        private readonly OrderDocumentService $documentService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('notification_number'));
        $status = trim((string) $request->string('status'));

        $rows = Order::query()
            ->with([
                'documents',
                'latestHpp' => fn ($query) => $query->select([
                    'hpps.id',
                    'hpps.order_id',
                    'hpps.nomor_order',
                    'hpps.total_keseluruhan',
                ]),
                'latestPurchaseOrder' => fn ($query) => $query->select([
                    'purchase_orders.id',
                    'purchase_orders.order_id',
                    'purchase_orders.purchase_order_number',
                    'purchase_orders.po_document_path',
                ]),
                'lhppBasts' => fn ($query) => $query
                    ->select([
                        'id',
                        'order_id',
                        'termin_type',
                        'nomor_order',
                        'deskripsi_pekerjaan',
                        'purchase_order_number',
                        'total_aktual_biaya',
                        'termin_1_nilai',
                        'termin_2_nilai',
                        'termin1_status',
                        'termin2_status',
                    ])
                    ->where('termin_type', 'termin_1')
                    ->with([
                        'terminTwo:id,order_id,parent_lhpp_bast_id,termin_type,nomor_order',
                        'lpjPpl:id,lhpp_bast_id,lpj_document_path_termin1,ppl_document_path_termin1,lpj_document_path_termin2,ppl_document_path_termin2',
                        'garansi:id,lhpp_bast_id,start_date,end_date,garansi_months',
                    ]),
            ])
            ->whereHas('purchaseOrder', function (Builder $query): void {
                $query
                    ->where('approve_manager', true)
                    ->whereNotNull('purchase_order_number')
                    ->whereRaw("TRIM(purchase_order_number) <> ''");
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('nomor_order', 'like', "%{$search}%")
                        ->orWhere('notifikasi', 'like', "%{$search}%")
                        ->orWhere('nama_pekerjaan', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->through(function (Order $order): array {
                /** @var LhppBast|null $terminOne */
                $terminOne = $order->lhppBasts->first();
                $lpjPpl = $terminOne?->lpjPpl;
                $garansi = $terminOne?->garansi;

                $hasHpp = (bool) $order->latestHpp;
                $hasPo = (bool) $order->latestPurchaseOrder?->po_document_path;
                $hasBast = (bool) $terminOne;
                $hasAbnormalitas = $order->documents->contains(
                    fn ($document): bool => $document->jenis_dokumen === OrderDocumentType::Abnormalitas
                );
                $hasLpjPpl = (bool) ($lpjPpl?->lpj_document_path_termin1 && $lpjPpl?->ppl_document_path_termin1);
                $isWithoutWarranty = (int) ($garansi?->garansi_months ?? -1) === 0;

                $isComplete = $hasAbnormalitas && $hasHpp && $hasPo && $hasBast && $hasLpjPpl;

                $paidPercent = 0;
                $paidAmount = 0;

                if ($isWithoutWarranty && ($terminOne?->termin1_status ?? 'belum') === 'sudah') {
                    $paidPercent = 100;
                    $paidAmount = (float) ($terminOne?->total_aktual_biaya ?? 0);
                } elseif (($terminOne?->termin2_status ?? 'belum') === 'sudah') {
                    $paidPercent = 100;
                    $paidAmount = (float) ($terminOne?->total_aktual_biaya ?? 0);
                } elseif (($terminOne?->termin1_status ?? 'belum') === 'sudah') {
                    $paidPercent = 95;
                    $paidAmount = (float) ($terminOne?->termin_1_nilai ?? 0);
                }

                return [
                    'id' => $order->id,
                    'nomor_order' => $order->nomor_order,
                    'notification_number' => $order->notifikasi,
                    'created_at' => $order->created_at,
                    'job_name' => $order->nama_pekerjaan,
                    'unit_kerja' => $order->unit_kerja,
                    'seksi' => $order->seksi,
                    'purchase_order_number' => $order->latestPurchaseOrder?->purchase_order_number,
                    'merged_document_url' => $hasAbnormalitas || $hasHpp || $hasPo || $hasBast
                        ? route('pkm.laporan.merged-documents', ['order' => $order->nomor_order])
                        : null,
                    'lpj_url_termin1' => ($lpjPpl?->lpj_document_path_termin1)
                        ? route('pkm.laporan.preview', ['nomorOrder' => $terminOne->nomor_order, 'kind' => 'lpj', 'termin' => 1])
                        : null,
                    'ppl_url_termin1' => ($lpjPpl?->ppl_document_path_termin1)
                        ? route('pkm.laporan.preview', ['nomorOrder' => $terminOne->nomor_order, 'kind' => 'ppl', 'termin' => 1])
                        : null,
                    'lpj_url_termin2' => (! $isWithoutWarranty && $lpjPpl?->lpj_document_path_termin2)
                        ? route('pkm.laporan.preview', ['nomorOrder' => $terminOne->nomor_order, 'kind' => 'lpj', 'termin' => 2])
                        : null,
                    'ppl_url_termin2' => (! $isWithoutWarranty && $lpjPpl?->ppl_document_path_termin2)
                        ? route('pkm.laporan.preview', ['nomorOrder' => $terminOne->nomor_order, 'kind' => 'ppl', 'termin' => 2])
                        : null,
                    'total_biaya' => (float) ($terminOne?->total_aktual_biaya ?? 0),
                    'paid_percent' => $paidPercent,
                    'paid_amount' => $paidAmount,
                    'garansi_start' => $garansi?->start_date,
                    'garansi_end' => $garansi?->end_date,
                    'garansi_months' => $garansi?->garansi_months,
                    'is_without_warranty' => $isWithoutWarranty,
                    'is_complete' => $isComplete,
                ];
            })
            ->withQueryString();

        if ($status !== '') {
            $filtered = collect($rows->items())->filter(function (array $row) use ($status): bool {
                return match ($status) {
                    'complete' => (bool) $row['is_complete'],
                    'incomplete' => ! $row['is_complete'],
                    default => true,
                };
            })->values();

            $rows->setCollection($filtered);
        }

        return view('dashboards.pkm', [
            'pageTitle' => 'Dokumen',
            'pageDescription' => 'Ringkasan compact dokumen pekerjaan, LPJ/PPL, pembayaran, dan garansi.',
            'documentRows' => $rows,
            'documentSearch' => $search,
            'documentStatus' => $status,
        ]);
    }

    public function previewLpjPpl(string $nomorOrder, string $kind, int $termin): BinaryFileResponse
    {
        abort_unless(in_array($kind, ['lpj', 'ppl'], true), Response::HTTP_NOT_FOUND);
        abort_unless(in_array($termin, [1, 2], true), Response::HTTP_NOT_FOUND);

        $lhppBast = LhppBast::query()
            ->with('lpjPpl')
            ->where('termin_type', 'termin_1')
            ->where('nomor_order', $nomorOrder)
            ->firstOrFail();

        $lpjPpl = $lhppBast->lpjPpl;

        abort_if(! $lpjPpl, Response::HTTP_NOT_FOUND, 'Dokumen LPJ/PPL tidak ditemukan.');

        $path = match ([$kind, $termin]) {
            ['lpj', 1] => $lpjPpl->lpj_document_path_termin1,
            ['ppl', 1] => $lpjPpl->ppl_document_path_termin1,
            ['lpj', 2] => $lpjPpl->lpj_document_path_termin2,
            ['ppl', 2] => $lpjPpl->ppl_document_path_termin2,
        };

        abort_if(blank($path), Response::HTTP_NOT_FOUND, 'Dokumen LPJ/PPL tidak ditemukan.');

        $absolutePath = storage_path('app/public/'.ltrim((string) $path, '/'));

        abort_unless(is_file($absolutePath), Response::HTTP_NOT_FOUND, 'File dokumen tidak ditemukan.');

        return response()->file($absolutePath);
    }

    public function mergedDocuments(Order $order): Response
    {
        try {
            $order->loadMissing([
                'documents',
                'latestHpp.order',
                'latestHpp.outlineAgreement.unitWork.department',
                'latestHpp.creator',
                'latestHpp.purchaseOrder',
                'latestHpp.signatures.signer:id,name,inisial',
                'latestPurchaseOrder',
                'lhppBasts' => fn ($query) => $query
                    ->whereIn('termin_type', ['termin_1', 'termin_2'])
                    ->with([
                        'images',
                        'garansi',
                        'signatures',
                        'hpp.order',
                        'hpp.outlineAgreement.unitWork.department',
                        'hpp.creator',
                        'purchaseOrder',
                        'order.purchaseOrder',
                    ]),
            ]);

            $abnormalitas = $order->documents->first(
                fn ($document): bool => $document->jenis_dokumen === OrderDocumentType::Abnormalitas
            );
            $hpp = $order->latestHpp;
            $purchaseOrder = $order->latestPurchaseOrder;
            $terminOne = $order->lhppBasts->firstWhere('termin_type', 'termin_1');
            $terminTwo = $order->lhppBasts->firstWhere('termin_type', 'termin_2');

            $pdfOutputs = [];

            if ($hpp) {
                $pdfOutputs[] = $this->hppPdfOutput($hpp);
            }

            if ($terminOne) {
                $pdfOutputs[] = $this->bastPdfOutput($terminOne);
            }

            if ($terminTwo) {
                $pdfOutputs[] = $this->bastPdfOutput($terminTwo);
            }

            if ($abnormalitas) {
                $abnormalitasPath = $this->documentService->absolutePath($abnormalitas);
                $abnormalitasMime = $this->documentService->mimeType($abnormalitas);

                if (
                    $abnormalitasPath
                    && is_file($abnormalitasPath)
                    && $this->isPdfPath($abnormalitasPath, $abnormalitasMime)
                ) {
                    $pdfOutputs[] = file_get_contents($abnormalitasPath) ?: '';
                }
            }

            if (
                $purchaseOrder?->po_document_path
                && Storage::disk('public')->exists($purchaseOrder->po_document_path)
            ) {
                $poPath = Storage::disk('public')->path($purchaseOrder->po_document_path);
                $poMime = Storage::disk('public')->mimeType($purchaseOrder->po_document_path);

                if ($this->isPdfPath($poPath, $poMime)) {
                    $pdfOutputs[] = Storage::disk('public')->get($purchaseOrder->po_document_path) ?: '';
                }
            }

            abort_if($pdfOutputs === [], Response::HTTP_NOT_FOUND, 'Belum ada dokumen PDF yang tersedia.');

            $documentTitle = trim($order->nomor_order.' - '.$order->nama_pekerjaan);
            $mergedPdf = $this->mergePdfOutputs($pdfOutputs, $documentTitle);

            abort_if($mergedPdf === '', Response::HTTP_INTERNAL_SERVER_ERROR, 'PDF gabungan tidak berhasil dibuat.');

            return response($mergedPdf, Response::HTTP_OK, $this->pdfInlineHeaders(
                $order->nomor_order.'-'.$order->nama_pekerjaan.'.pdf'
            ));
        } catch (Throwable $exception) {
            $statusCode = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            Log::error('Failed to merge PKM document report.', [
                'status_code' => $statusCode,
                'order_id' => $order->id,
                'nomor_order' => $order->nomor_order,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort($statusCode, $exception->getMessage());
        }
    }

    private function hppPdfOutput(Hpp $hpp): string
    {
        $finalDocumentSignature = $hpp->finalSignedDocumentSignature();

        if ($finalDocumentSignature?->hasUploadedSignedDocument()) {
            abort_unless(
                Storage::disk('public')->exists($finalDocumentSignature->signed_document_path),
                Response::HTTP_NOT_FOUND,
                'File HPP final tidak ditemukan.'
            );

            $path = Storage::disk('public')->path($finalDocumentSignature->signed_document_path);
            $mime = Storage::disk('public')->mimeType($finalDocumentSignature->signed_document_path);

            abort_unless($this->isPdfPath($path, $mime), Response::HTTP_UNPROCESSABLE_ENTITY, 'Dokumen HPP final harus berupa PDF.');

            return Storage::disk('public')->get($finalDocumentSignature->signed_document_path) ?: '';
        }

        return Pdf::loadView('admin.hpp.hpppdf', [
            'hpp' => $hpp,
        ])->setPaper('a4', 'landscape')->output();
    }

    private function bastPdfOutput(LhppBast $lhpp): string
    {
        $finalDocumentSignature = $lhpp->finalSignedDocumentSignature();

        if ($finalDocumentSignature?->hasUploadedSignedDocument()) {
            abort_unless(
                Storage::disk('public')->exists($finalDocumentSignature->signed_document_path),
                Response::HTTP_NOT_FOUND,
                'File BAST final tidak ditemukan.'
            );

            $path = Storage::disk('public')->path($finalDocumentSignature->signed_document_path);
            $mime = Storage::disk('public')->mimeType($finalDocumentSignature->signed_document_path);

            abort_unless($this->isPdfPath($path, $mime), Response::HTTP_UNPROCESSABLE_ENTITY, 'Dokumen BAST final harus berupa PDF.');

            return Storage::disk('public')->get($finalDocumentSignature->signed_document_path) ?: '';
        }

        return Pdf::loadView('pkm.lhpp.pdf', [
            'lhpp' => $lhpp,
            'materialItems' => collect($lhpp->material_items ?? []),
            'serviceItems' => collect($lhpp->service_items ?? []),
        ])->setPaper('a4', 'portrait')->output();
    }

    /**
     * @param  array<int, string>  $pdfOutputs
     */
    private function mergePdfOutputs(array $pdfOutputs, string $title = ''): string
    {
        return app(PdfMergeService::class)->merge($pdfOutputs, $title, [
            'controller' => static::class,
        ]);
    }

    private function isPdfPath(string $path, ?string $mimeType): bool
    {
        return str_contains(strtolower((string) $mimeType), 'pdf')
            || strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
    }

    /**
     * @return array<string, string>
     */
    private function pdfInlineHeaders(string $filename): array
    {
        $safeFilename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?: 'dokumen-pekerjaan.pdf';

        return [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', trim($safeFilename, '-')),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}
