<?php

namespace App\Http\Controllers\User;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Http\Controllers\Controller;
use App\Models\BengkelPic;
use App\Models\BengkelTask;
use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderWorkshop;
use App\Models\QualityControlReport;
use App\Models\QualityControlSignature;
use App\Services\QualityControl\QualityControlSignatureService;
use App\Support\ApprovalWhatsappLink;
use App\Support\SignatureImageStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\Response;

class OrderTrackingController extends Controller
{
    public function __construct(
        private readonly QualityControlSignatureService $qualityControlSignatureService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'notification_number' => trim((string) $request->string('notification_number')),
            'unit_work' => trim((string) $request->string('unit_work')),
            'sortOrder' => $request->string('sortOrder')->toString() === 'oldest' ? 'oldest' : 'latest',
            'entries' => max(10, min(100, (int) $request->integer('entries', 10))),
        ];

        $filteredQuery = $this->applyDashboardFilters($this->baseQuery(), $filters);

        $orders = (clone $filteredQuery)
            ->when(
                $filters['sortOrder'] === 'oldest',
                fn (Builder $query) => $query->orderBy('tanggal_order')->orderBy('id'),
                fn (Builder $query) => $query->orderByDesc('tanggal_order')->orderByDesc('id')
            )
            ->paginate($filters['entries'])
            ->withQueryString();

        $cards = $orders->getCollection()
            ->map(fn (Order $order) => $this->mapOrderForCard($order))
            ->values();

        $orders->setCollection($cards);

        $allOrders = (clone $filteredQuery)->get();

        return view('user.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'stats' => [
                'total_orders' => $allOrders->count(),
                'emergency_orders' => $allOrders->filter(
                    fn (Order $order) => Order::priorityPrimaryFor($order->prioritas) === 'emergency'
                )->count(),
                'po_ready' => $allOrders->filter(
                    fn (Order $order) => filled($order->purchaseOrder?->purchase_order_number)
                )->count(),
                'bast_ready' => $allOrders->filter(
                    fn (Order $order) => $order->lhppBasts->where('termin_type', 'termin_1')->isNotEmpty()
                )->count(),
            ],
            'units' => Order::query()
                ->whereNotNull('unit_kerja')
                ->where('unit_kerja', '!=', '')
                ->distinct()
                ->orderBy('unit_kerja')
                ->pluck('unit_kerja')
                ->values(),
            'chartApproved' => $this->buildApprovedChartData($allOrders),
            'chartBiaya' => $this->buildBiayaChartData($allOrders),
        ]);
    }

    public function show(Order $order): View
    {
        $order = $this->ownedOrder($order);

        return view('user.orders.show', [
            'order' => $this->mapOrderForDetail($order),
        ]);
    }

    public function previewDocument(Order $order, OrderDocument $document): Response
    {
        $order = $this->ownedOrder($order);

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

    public function scopeOfWorkPdf(Order $order): Response
    {
        $order = $this->ownedOrder($order);
        $scopeOfWork = $order->scopeOfWork;

        abort_if(! $scopeOfWork, 404);

        $scopeOfWork->loadMissing('creator');
        $order->loadMissing('creator');

        $pdf = Pdf::loadView('admin.orders.scope-of-work-pdf', [
            'order' => $order,
            'scopeOfWork' => $scopeOfWork,
            'scopeItems' => $scopeOfWork->scope_items ?? [],
            'signaturePath' => SignatureImageStorage::imageSource($scopeOfWork->tanda_tangan),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('scope-of-work-'.$order->nomor_order.'.pdf');
    }

    public function initialWorkPdf(Order $order): Response
    {
        $order = $this->ownedOrder($order);
        $initialWork = $order->initialWork;

        abort_if(! $initialWork, 404);

        $pdf = Pdf::loadView('admin.orders.initial-work-pdf', [
            'order' => $order,
            'initialWork' => $initialWork,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('initial-work-'.$order->nomor_order.'.pdf');
    }

    public function hppPdf(Order $order): Response
    {
        $order = $this->ownedOrder($order);
        $hpp = $order->latestHpp;

        abort_if(! $hpp, 404);

        $hpp->loadMissing(['order', 'outlineAgreement', 'creator', 'signatures.signer:id,name,inisial']);

        $finalSignedDocument = $hpp->finalSignedDocumentSignature();

        if ($finalSignedDocument?->hasUploadedSignedDocument()) {
            abort_unless(Storage::disk('public')->exists($finalSignedDocument->signed_document_path), 404);

            return response()->file(
                Storage::disk('public')->path($finalSignedDocument->signed_document_path),
                $this->pdfNoCacheHeaders([
                    'Content-Type' => $finalSignedDocument->signed_document_mime_type ?: (Storage::disk('public')->mimeType($finalSignedDocument->signed_document_path) ?: 'application/octet-stream'),
                    'Content-Disposition' => 'inline; filename="'.$this->safeFilename(
                        $finalSignedDocument->signed_document_original_name ?: basename($finalSignedDocument->signed_document_path)
                    ).'"',
                ]),
            );
        }

        $pdf = Pdf::loadView('admin.hpp.hpppdf', [
            'hpp' => $hpp,
        ])->setPaper('a4', 'landscape');

        $response = $pdf->stream('hpp-'.$hpp->nomor_order.'.pdf');

        foreach ($this->pdfNoCacheHeaders() as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    public function purchaseOrderDocument(Order $order): Response
    {
        $order = $this->ownedOrder($order);
        $path = $order->purchaseOrder?->po_document_path;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return response()->file(
            Storage::disk('public')->path($path),
            [
                'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            ],
        );
    }

    public function qualityControlPdf(Order $order): Response
    {
        $order = $this->ownedOrder($order);
        $report = $order->latestQualityControlReport;

        abort_if(! $report, 404);

        $report->loadMissing(['files', 'signatures']);

        $type = $report->type;
        abort_unless(in_array($type, [QualityControlReport::TYPE_FABRICATION, QualityControlReport::TYPE_REFURBISH], true), 404);

        $paper = $type === QualityControlReport::TYPE_REFURBISH ? 'landscape' : 'portrait';
        $filename = 'qc-'.$type.'-'.$order->nomor_order.'.pdf';

        return Pdf::loadView("admin.orders.workshop.quality-control.pdf.{$type}", [
            'order' => $order,
            'report' => $report,
            'payload' => $report->payload ?: [],
            'filesByCategory' => $report->files->groupBy('category'),
        ])->setPaper('a4', $paper)->stream($filename);
    }

    public function bastPdf(Order $order, string $termin): Response
    {
        $order = $this->ownedOrder($order);
        $terminType = $termin === 'termin-2' ? 'termin_2' : 'termin_1';

        $lhpp = $order->lhppBasts()
            ->where('termin_type', $terminType)
            ->with([
                'images',
                'signatures',
                'parentLhppBast.images',
                'parentLhppBast.signatures',
                'parentLhppBast.purchaseOrder:id,order_id,purchase_order_number',
                'parentLhppBast.order.purchaseOrder:id,order_id,purchase_order_number',
                'purchaseOrder:id,order_id,purchase_order_number',
                'order.purchaseOrder:id,order_id,purchase_order_number',
                'hpp.order',
                'hpp.outlineAgreement.unitWork.department',
                'hpp.creator',
            ])
            ->firstOrFail();

        $finalDocumentSignature = $lhpp->finalSignedDocumentSignature();

        if ($finalDocumentSignature?->hasUploadedSignedDocument()) {
            abort_unless(Storage::disk('public')->exists($finalDocumentSignature->signed_document_path), Response::HTTP_NOT_FOUND);

            return response()->file(
                Storage::disk('public')->path($finalDocumentSignature->signed_document_path),
                [
                    'Content-Type' => $finalDocumentSignature->signed_document_mime_type
                        ?: (Storage::disk('public')->mimeType($finalDocumentSignature->signed_document_path) ?: 'application/octet-stream'),
                    'Content-Disposition' => sprintf(
                        'inline; filename="%s"',
                        $finalDocumentSignature->signed_document_original_name
                            ?: basename($finalDocumentSignature->signed_document_path)
                    ),
                ],
            );
        }

        $bastPdf = Pdf::loadView('pkm.lhpp.pdf', [
            'lhpp' => $lhpp,
            'materialItems' => collect($lhpp->material_items ?? []),
            'serviceItems' => collect($lhpp->service_items ?? []),
        ])->setPaper('a4', 'portrait')->output();

        $attachedHpp = $lhpp->hpp ?: $order->latestHpp;
        $terminSlug = $terminType === 'termin_2' ? 'termin-2' : 'termin-1';
        $terminOnePdf = null;

        if ($terminType === 'termin_2' && $lhpp->parentLhppBast) {
            $terminOnePdf = Pdf::loadView('pkm.lhpp.pdf', [
                'lhpp' => $lhpp->parentLhppBast,
                'materialItems' => collect($lhpp->parentLhppBast->material_items ?? []),
                'serviceItems' => collect($lhpp->parentLhppBast->service_items ?? []),
            ])->setPaper('a4', 'portrait')->output();
        }

        if (! $attachedHpp) {
            $pdfOutput = $terminOnePdf
                ? $this->mergePdfOutputs([$bastPdf, $terminOnePdf])
                : $bastPdf;

            return response($pdfOutput, Response::HTTP_OK, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="%s"', 'bast-'.$terminSlug.'-'.$order->nomor_order.'.pdf'),
            ]);
        }

        $hppPdf = Pdf::loadView('admin.hpp.hpppdf', [
            'hpp' => $attachedHpp,
        ])->setPaper('a4', 'landscape')->output();

        $mergedPdf = $this->mergePdfOutputs(array_filter([$bastPdf, $terminOnePdf, $hppPdf]));

        return response($mergedPdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', 'bast-'.$terminSlug.'-'.$order->nomor_order.'.pdf'),
        ]);
    }

    public function previewLpjPpl(Order $order, string $kind, int $termin): Response
    {
        $order = $this->ownedOrder($order);

        abort_unless(in_array($kind, ['lpj', 'ppl'], true), 404);
        abort_unless(in_array($termin, [1, 2], true), 404);

        $terminOne = $order->lhppBasts
            ->where('termin_type', 'termin_1')
            ->first();

        abort_if(! $terminOne?->lpjPpl, 404);

        $path = match ([$kind, $termin]) {
            ['lpj', 1] => $terminOne->lpjPpl->lpj_document_path_termin1,
            ['ppl', 1] => $terminOne->lpjPpl->ppl_document_path_termin1,
            ['lpj', 2] => $terminOne->lpjPpl->lpj_document_path_termin2,
            ['ppl', 2] => $terminOne->lpjPpl->ppl_document_path_termin2,
        };

        abort_if(blank($path), 404);

        $absolutePath = storage_path('app/public/'.ltrim((string) $path, '/'));
        abort_unless(is_file($absolutePath), 404);

        return response()->file($absolutePath);
    }

    private function baseQuery(): Builder
    {
        return Order::query()
            ->with([
                'documents',
                'scopeOfWork',
                'initialWork',
                'latestHpp',
                'budgetVerification',
                'purchaseOrder',
                'orderWorkshop',
                'latestQualityControlReport.files',
                'latestQualityControlReport.signatures',
                'lhppBasts' => fn ($query) => $query
                    ->with(['lpjPpl', 'garansi', 'terminTwo'])
                    ->orderBy('id'),
            ]);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyDashboardFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['notification_number'] !== '', function (Builder $builder) use ($filters): void {
                $needle = $filters['notification_number'];

                $builder->where(function (Builder $query) use ($needle): void {
                    $query
                        ->where('nomor_order', 'like', "%{$needle}%")
                        ->orWhere('notifikasi', 'like', "%{$needle}%");
                });
            })
            ->when(
                $filters['unit_work'] !== '',
                fn (Builder $builder) => $builder->where('unit_kerja', $filters['unit_work'])
            );
    }

    private function ownedOrder(Order $order): Order
    {
        $order->loadMissing([
            'documents',
            'scopeOfWork',
            'initialWork',
            'latestHpp.order',
            'latestHpp.outlineAgreement',
            'latestHpp.creator',
            'latestHpp.signatures',
            'budgetVerification',
            'purchaseOrder',
            'orderWorkshop',
            'latestQualityControlReport.files',
            'latestQualityControlReport.signatures',
            'lhppBasts.lpjPpl',
            'lhppBasts.garansi',
            'lhppBasts.terminTwo',
        ]);

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapOrderForCard(Order $order): array
    {
        $isWorkshopOnly = $this->isWorkshopOnly($order);
        $progress = $this->resolveProgress($order);
        $terminOne = $order->lhppBasts->firstWhere('termin_type', 'termin_1');
        $terminTwo = $order->lhppBasts->firstWhere('termin_type', 'termin_2') ?: $terminOne?->terminTwo;
        $garansi = $terminOne?->garansi;
        $isWithoutWarranty = (int) ($garansi?->garansi_months ?? -1) === 0;
        $terminTwo = $isWithoutWarranty ? null : $terminTwo;
        $abnormalitasDocument = $this->resolveOrderDocumentLink($order, 'abnormalitas');
        $gambarTeknikDocument = $this->resolveOrderDocumentLink($order, 'gambar_teknik');
        $qualityControlDocument = $this->resolveQualityControlDocumentLink($order);

        return [
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_order' => $order->tanggal_order?->format('d/m/Y'),
            'prioritas_label' => $order->priorityLabel(),
            'prioritas_badge_classes' => $order->priorityBadgeClasses(),
            'status_label' => $isWorkshopOnly ? $this->resolveWorkshopPhase($order) : $this->resolveCurrentPhase($order),
            'status_tone' => $isWorkshopOnly ? $this->resolveWorkshopPhaseTone($order) : $this->resolveCurrentPhaseTone($order),
            'progress' => $progress,
            'is_workshop_only' => $isWorkshopOnly,
            'document_completion_percentage' => $order->documentCompletionPercentage(),
            'show_url' => route('user.orders.show', $order),
            'quick_links' => [
                'abnormalitas' => $abnormalitasDocument['url'] ?? null,
                'gambar_teknik' => $gambarTeknikDocument['url'] ?? null,
                'scope_of_work' => $order->scopeOfWork ? route('user.orders.scope-of-work.pdf', $order) : null,
                'hpp' => (! $isWorkshopOnly && $order->latestHpp) ? route('user.orders.hpp.pdf', $order) : null,
                'bast_termin_1' => (! $isWorkshopOnly && $terminOne) ? route('user.orders.bast.pdf', ['order' => $order, 'termin' => 'termin-1']) : null,
                'bast_termin_2' => (! $isWorkshopOnly && $terminTwo) ? route('user.orders.bast.pdf', ['order' => $order, 'termin' => 'termin-2']) : null,
                'quality_control' => $qualityControlDocument['url'] ?? null,
            ],
            'garansi' => $garansi ? [
                'months' => $garansi->garansi_months,
                'end' => $garansi->end_date?->format('d/m/Y'),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapOrderForDetail(Order $order): array
    {
        $isWorkshopOnly = $this->isWorkshopOnly($order);
        $workshopTask = $isWorkshopOnly ? $this->resolveBengkelTask($order) : null;
        $progress = $this->resolveProgress($order);
        $terminOne = $order->lhppBasts->firstWhere('termin_type', 'termin_1');
        $terminTwo = $order->lhppBasts->firstWhere('termin_type', 'termin_2') ?: $terminOne?->terminTwo;
        $lpjPpl = $terminOne?->lpjPpl;
        $garansi = $terminOne?->garansi;
        $isWithoutWarranty = (int) ($garansi?->garansi_months ?? -1) === 0;
        $terminTwo = $isWithoutWarranty ? null : $terminTwo;
        $hppDocument = $this->resolveHppDocumentLink($order);
        $qualityControlDocument = $this->resolveQualityControlDocumentLink($order);
        $qualityControlApproval = $this->resolveQualityControlApprovalShareInfo($order->latestQualityControlReport);
        $documentPreviewItems = [
            [
                'key' => 'hpp',
                'title' => 'HPP',
                'label' => $hppDocument['label'] ?? 'HPP',
                'url' => $hppDocument['url'] ?? null,
                'preview_type' => $hppDocument['preview_type'] ?? 'pdf',
                'icon' => 'file-text',
                'tone' => 'blue',
            ],
            [
                'key' => 'abnormalitas',
                'title' => 'Abnormalitas',
                'label' => $this->resolveOrderDocumentLink($order, 'abnormalitas')['label'] ?? 'Abnormalitas',
                'url' => $this->resolveOrderDocumentLink($order, 'abnormalitas')['url'] ?? null,
                'preview_type' => $this->resolveOrderDocumentLink($order, 'abnormalitas')['preview_type'] ?? 'file',
                'icon' => 'triangle-alert',
                'tone' => 'rose',
            ],
            [
                'key' => 'gambar_teknik',
                'title' => 'Gambar Teknik',
                'label' => $this->resolveOrderDocumentLink($order, 'gambar_teknik')['label'] ?? 'Gambar Teknik',
                'url' => $this->resolveOrderDocumentLink($order, 'gambar_teknik')['url'] ?? null,
                'preview_type' => $this->resolveOrderDocumentLink($order, 'gambar_teknik')['preview_type'] ?? 'file',
                'icon' => 'image',
                'tone' => 'blue',
            ],
            [
                'key' => 'scope_of_work',
                'title' => 'Scope of Work',
                'label' => 'Scope of Work',
                'url' => $order->scopeOfWork ? route('user.orders.scope-of-work.pdf', $order) : null,
                'preview_type' => 'pdf',
                'icon' => 'clipboard-list',
                'tone' => 'emerald',
            ],
            [
                'key' => 'initial_work',
                'title' => 'Initial Work',
                'label' => 'Initial Work',
                'url' => $order->initialWork ? route('user.orders.initial-work.pdf', $order) : null,
                'preview_type' => 'pdf',
                'icon' => 'clipboard-pen-line',
                'tone' => 'violet',
            ],
            [
                'key' => 'quality_control',
                'title' => 'Quality Control',
                'label' => $qualityControlDocument['label'] ?? 'PDF Quality Control',
                'url' => $qualityControlDocument['url'] ?? null,
                'preview_type' => 'pdf',
                'icon' => 'clipboard-check',
                'tone' => 'emerald',
            ],
            [
                'key' => 'purchase_order',
                'title' => 'Dokumen PO',
                'label' => $order->purchaseOrder?->purchase_order_number ? 'PO : '.$order->purchaseOrder->purchase_order_number : 'Dokumen PO',
                'url' => filled($order->purchaseOrder?->po_document_path) ? route('user.orders.purchase-order.document', $order) : null,
                'preview_type' => $this->detectPreviewTypeFromFilename($order->purchaseOrder?->po_document_path),
                'icon' => 'receipt',
                'tone' => 'emerald',
            ],
            [
                'key' => 'bast_termin_1',
                'title' => 'BAST Termin 1',
                'label' => 'BAST Termin 1',
                'url' => $terminOne ? route('user.orders.bast.pdf', ['order' => $order, 'termin' => 'termin-1']) : null,
                'preview_type' => 'pdf',
                'icon' => 'file-badge',
                'tone' => 'orange',
            ],
            [
                'key' => 'bast_termin_2',
                'title' => 'BAST Termin 2',
                'label' => 'BAST Termin 2',
                'url' => $terminTwo ? route('user.orders.bast.pdf', ['order' => $order, 'termin' => 'termin-2']) : null,
                'preview_type' => 'pdf',
                'icon' => 'files',
                'tone' => 'orange',
            ],
            [
                'key' => 'lpj_termin_1',
                'title' => 'LPJ Termin 1',
                'label' => $lpjPpl?->lpj_number_termin1 ?: 'LPJ Termin 1',
                'url' => filled($lpjPpl?->lpj_number_termin1) ? route('user.orders.laporan.preview', ['order' => $order, 'kind' => 'lpj', 'termin' => 1]) : null,
                'preview_type' => $this->detectPreviewTypeFromFilename($lpjPpl?->lpj_document_path_termin1),
                'icon' => 'file-chart-column',
                'tone' => 'slate',
            ],
            [
                'key' => 'ppl_termin_1',
                'title' => 'PPL Termin 1',
                'label' => $lpjPpl?->ppl_number_termin1 ?: 'PPL Termin 1',
                'url' => filled($lpjPpl?->ppl_number_termin1) ? route('user.orders.laporan.preview', ['order' => $order, 'kind' => 'ppl', 'termin' => 1]) : null,
                'preview_type' => $this->detectPreviewTypeFromFilename($lpjPpl?->ppl_document_path_termin1),
                'icon' => 'file-bar-chart-2',
                'tone' => 'slate',
            ],
            [
                'key' => 'lpj_termin_2',
                'title' => 'LPJ Termin 2',
                'label' => $lpjPpl?->lpj_number_termin2 ?: 'LPJ Termin 2',
                'url' => filled($lpjPpl?->lpj_number_termin2) ? route('user.orders.laporan.preview', ['order' => $order, 'kind' => 'lpj', 'termin' => 2]) : null,
                'preview_type' => $this->detectPreviewTypeFromFilename($lpjPpl?->lpj_document_path_termin2),
                'icon' => 'file-chart-column',
                'tone' => 'slate',
            ],
            [
                'key' => 'ppl_termin_2',
                'title' => 'PPL Termin 2',
                'label' => $lpjPpl?->ppl_number_termin2 ?: 'PPL Termin 2',
                'url' => filled($lpjPpl?->ppl_number_termin2) ? route('user.orders.laporan.preview', ['order' => $order, 'kind' => 'ppl', 'termin' => 2]) : null,
                'preview_type' => $this->detectPreviewTypeFromFilename($lpjPpl?->ppl_document_path_termin2),
                'icon' => 'file-bar-chart-2',
                'tone' => 'slate',
            ],
        ];

        if ($isWithoutWarranty) {
            $documentPreviewItems = array_values(array_filter(
                $documentPreviewItems,
                fn (array $item): bool => ! in_array($item['key'], ['bast_termin_2', 'lpj_termin_2', 'ppl_termin_2'], true),
            ));
        }

        if ($isWorkshopOnly) {
            $documentPreviewItems = array_values(array_filter(
                $documentPreviewItems,
                fn (array $item): bool => in_array($item['key'], ['abnormalitas', 'gambar_teknik', 'scope_of_work', 'quality_control'], true),
            ));
        }

        $timeline = $isWorkshopOnly
            ? [
                [
                    'label' => 'Order Dibuat',
                    'value' => $order->tanggal_order?->format('d/m/Y') ?? '-',
                    'tone' => 'done',
                ],
                [
                    'label' => 'Persetujuan Awal',
                    'value' => $order->catatan_status?->label() ?? 'Pending',
                    'tone' => $order->catatan_status && $order->catatan_status !== OrderUserNoteStatus::Pending ? 'done' : 'waiting',
                ],
                [
                    'label' => 'Pekerjaan Bengkel',
                    'value' => $this->resolveWorkshopPhase($order),
                    'detail' => $qualityControlApproval['timeline_detail'] ?? null,
                    'tone' => $this->resolveWorkshopPhaseTone($order) === 'emerald' ? 'done' : 'waiting',
                ],
            ]
            : [
                [
                    'label' => 'Order Dibuat',
                    'value' => $order->tanggal_order?->format('d/m/Y') ?? '-',
                    'tone' => 'done',
                ],
                [
                    'label' => 'Persetujuan Awal',
                    'value' => $order->catatan_status?->label() ?? 'Pending',
                    'tone' => $order->catatan_status && $order->catatan_status !== OrderUserNoteStatus::Pending ? 'done' : 'waiting',
                ],
                [
                    'label' => 'HPP',
                    'value' => $order->latestHpp ? (Hpp::statusOptions()[$order->latestHpp->status] ?? ucfirst($order->latestHpp->status)) : 'Belum dibuat',
                    'tone' => $order->latestHpp ? 'done' : 'waiting',
                ],
                [
                    'label' => 'Verifikasi Anggaran',
                    'value' => $order->budgetVerification?->status_anggaran ?? 'Belum diverifikasi',
                    'tone' => match ($order->budgetVerification?->status_anggaran) {
                        'Tersedia' => 'done',
                        'Tidak Tersedia' => 'danger',
                        default => 'waiting',
                    },
                ],
                [
                    'label' => 'Purchase Order',
                    'value' => $order->purchaseOrder?->purchase_order_number ?? 'Belum tersedia',
                    'tone' => filled($order->purchaseOrder?->purchase_order_number) ? 'done' : 'waiting',
                ],
                [
                    'label' => 'BAST Termin 1',
                    'value' => $terminOne ? 'Siap dilihat' : 'Belum tersedia',
                    'tone' => $terminOne ? 'done' : 'waiting',
                ],
                [
                    'label' => 'BAST Termin 2',
                    'value' => $terminTwo ? 'Siap dilihat' : 'Belum tersedia',
                    'tone' => $terminTwo ? 'done' : 'waiting',
                ],
                [
                    'label' => 'Garansi',
                    'value' => $garansi ? sprintf('%s bulan', (int) $garansi->garansi_months) : 'Belum tersedia',
                    'tone' => $garansi ? 'done' : 'waiting',
                ],
            ];

        return [
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'deskripsi' => $isWorkshopOnly ? 'Order pekerjaan bengkel' : $order->deskripsi,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_order' => $order->tanggal_order?->format('d/m/Y'),
            'target_selesai_order' => $order->target_selesai?->format('d/m/Y'),
            'prioritas_label' => $order->priorityLabel(),
            'prioritas_badge_classes' => $order->priorityBadgeClasses(),
            'approval_label' => $order->catatan_status?->label() ?? OrderUserNoteStatus::Pending->label(),
            'approval_note' => $order->catatan,
            'progress' => $progress,
            'timeline' => $timeline,
            'is_workshop_only' => $isWorkshopOnly,
            'workshop' => [
                'status' => $this->resolveWorkshopPhase($order),
                'task_name' => $workshopTask?->job_name ?: $order->nama_pekerjaan,
                'regu' => $workshopTask?->catatan ?: $order->catatan,
                'pics' => $this->resolveBengkelTaskPicAssignments($workshopTask),
                'konfirmasi_anggaran' => $order->orderWorkshop?->konfirmasi_anggaran,
                'status_anggaran' => $order->orderWorkshop?->status_anggaran,
                'status_material' => $order->orderWorkshop?->status_material,
                'keterangan_konfirmasi' => $order->orderWorkshop?->keterangan_konfirmasi,
                'keterangan_anggaran' => $order->orderWorkshop?->keterangan_anggaran,
                'keterangan_material' => $order->orderWorkshop?->keterangan_material,
                'keterangan_progress' => $order->orderWorkshop?->keterangan_progress,
                'catatan' => $order->orderWorkshop?->catatan ?: $order->catatan,
            ],
            'quality_control' => [
                'approval' => $qualityControlApproval,
            ],
            'documents' => [
                'abnormalitas' => $this->resolveOrderDocumentLink($order, 'abnormalitas'),
                'gambar_teknik' => $this->resolveOrderDocumentLink($order, 'gambar_teknik'),
                'scope_of_work' => $order->scopeOfWork ? route('user.orders.scope-of-work.pdf', $order) : null,
                'initial_work' => $order->initialWork ? route('user.orders.initial-work.pdf', $order) : null,
                'quality_control' => $qualityControlDocument,
                'hpp' => $hppDocument ? $hppDocument['url'] : null,
                'purchase_order' => filled($order->purchaseOrder?->po_document_path) ? route('user.orders.purchase-order.document', $order) : null,
                'bast_termin_1' => $terminOne ? route('user.orders.bast.pdf', ['order' => $order, 'termin' => 'termin-1']) : null,
                'bast_termin_2' => $terminTwo ? route('user.orders.bast.pdf', ['order' => $order, 'termin' => 'termin-2']) : null,
                'lpj_termin_1' => filled($lpjPpl?->lpj_number_termin1) ? [
                    'label' => $lpjPpl->lpj_number_termin1,
                    'url' => route('user.orders.laporan.preview', ['order' => $order, 'kind' => 'lpj', 'termin' => 1]),
                ] : null,
                'ppl_termin_1' => filled($lpjPpl?->ppl_number_termin1) ? [
                    'label' => $lpjPpl->ppl_number_termin1,
                    'url' => route('user.orders.laporan.preview', ['order' => $order, 'kind' => 'ppl', 'termin' => 1]),
                ] : null,
                'lpj_termin_2' => (! $isWithoutWarranty && filled($lpjPpl?->lpj_number_termin2)) ? [
                    'label' => $lpjPpl->lpj_number_termin2,
                    'url' => route('user.orders.laporan.preview', ['order' => $order, 'kind' => 'lpj', 'termin' => 2]),
                ] : null,
                'ppl_termin_2' => (! $isWithoutWarranty && filled($lpjPpl?->ppl_number_termin2)) ? [
                    'label' => $lpjPpl->ppl_number_termin2,
                    'url' => route('user.orders.laporan.preview', ['order' => $order, 'kind' => 'ppl', 'termin' => 2]),
                ] : null,
            ],
            'document_preview_items' => $documentPreviewItems,
            'budget' => [
                'status' => $order->budgetVerification?->status_anggaran ?? 'Belum diverifikasi',
                'kategori_item' => $order->budgetVerification?->kategori_item,
                'kategori_biaya' => $order->budgetVerification?->kategori_biaya,
                'cost_element' => $order->budgetVerification?->cost_element,
                'catatan' => $order->budgetVerification?->catatan,
            ],
            'purchase_order' => [
                'number' => $order->purchaseOrder?->purchase_order_number,
                'target' => $order->purchaseOrder?->target_penyelesaian?->format('d/m/Y'),
                'admin_note' => $order->purchaseOrder?->admin_note,
            ],
            'hpp' => [
                'status' => $order->latestHpp ? (Hpp::statusOptions()[$order->latestHpp->status] ?? ucfirst($order->latestHpp->status)) : 'Belum dibuat',
                'total' => $order->latestHpp?->total_keseluruhan,
                'approval' => $this->resolveHppApprovalShareInfo($order->latestHpp),
            ],
            'garansi' => $garansi ? [
                'months' => $garansi->garansi_months,
                'start' => $garansi->start_date?->format('d/m/Y'),
                'end' => $garansi->end_date?->format('d/m/Y'),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveHppApprovalShareInfo(?Hpp $hpp): ?array
    {
        if (! $hpp) {
            return null;
        }

        $hpp->loadMissing('signatures');

        $activeSignature = $hpp->signatures
            ->first(fn ($signature): bool => $signature->isPending());
        $signatureLinks = $hpp->signatures
            ->filter(fn ($signature): bool => $signature->isPending() && ! $signature->tokenExpired() && filled($signature->token))
            ->map(fn ($signature): array => [
                'step' => $signature->step_order,
                'role_label' => $signature->role_label,
                'signer_name' => $signature->signer_name_snapshot,
                'status' => $signature->status,
                'status_label' => match ($signature->status) {
                    HppSignature::STATUS_PENDING => $signature->tokenExpired() ? 'Token kedaluwarsa' : 'Menunggu TTD',
                    HppSignature::STATUS_SIGNED => 'Sudah TTD',
                    HppSignature::STATUS_LOCKED => 'Belum aktif',
                    HppSignature::STATUS_SKIPPED => 'Dilewati',
                    default => ucfirst((string) $signature->status),
                },
                'link' => route('approval.hpp.show', $signature->token),
                'whatsapp_url' => ApprovalWhatsappLink::forHpp($signature),
                'expires_at' => $signature->token_expires_at?->format('d/m/Y H:i'),
                'is_expired' => $signature->tokenExpired(),
                'is_active' => $signature->isPending(),
            ])
            ->values()
            ->all();

        if (! $activeSignature) {
            return [
                'state' => $hpp->approvalCompleted() ? 'completed' : 'none',
                'label' => $hpp->approvalCompleted() ? 'Approval HPP selesai' : 'Tidak ada token aktif',
                'link' => null,
                'signer_name' => null,
                'role_label' => null,
                'step' => null,
                'total_steps' => $hpp->approvalStepCount(),
                'expires_at' => null,
                'is_expired' => false,
                'links' => $signatureLinks,
            ];
        }

        return [
            'state' => $activeSignature->tokenExpired() ? 'expired' : 'pending',
            'label' => $activeSignature->tokenExpired() ? 'Token TTD kedaluwarsa' : 'Menunggu TTD',
            'link' => $activeSignature->approvalUrl(),
            'signer_name' => $activeSignature->signer_name_snapshot,
            'role_label' => $activeSignature->role_label,
            'step' => $activeSignature->step_order,
            'total_steps' => $hpp->approvalStepCount(),
            'expires_at' => $activeSignature->token_expires_at?->format('d/m/Y H:i'),
            'is_expired' => $activeSignature->tokenExpired(),
            'links' => $signatureLinks,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveQualityControlApprovalShareInfo(?QualityControlReport $report): ?array
    {
        if (! $report) {
            return null;
        }

        $this->qualityControlSignatureService->ensureSignatureChain($report);
        $report->loadMissing('signatures');
        $report->refresh()->loadMissing('signatures');

        $makerSignature = collect($report->payload['signature'] ?? []);
        $makerName = trim((string) ($makerSignature->get('signer_name') ?: $makerSignature->get('name')));
        $makerSignedAt = $makerSignature->get('signed_at');
        $makerSigned = filled($makerSignature->get('signature_data'))
            || filled($makerSignedAt)
            || filled($makerName);

        $approvalSignatures = $report->signatures
            ->sortBy('step_order')
            ->values();
        $activeSignature = $approvalSignatures
            ->first(fn (QualityControlSignature $signature): bool => $signature->isPending());
        $missingSignature = $approvalSignatures
            ->firstWhere('status', QualityControlSignature::STATUS_MISSING);
        $completedSteps = ($makerSigned ? 1 : 0)
            + $approvalSignatures
                ->filter(fn (QualityControlSignature $signature): bool => $signature->isSigned())
                ->count();
        $totalSteps = 1 + max(2, $approvalSignatures->count());
        $signatureLinks = $approvalSignatures
            ->filter(
                fn (QualityControlSignature $signature): bool => $signature->isPending()
                    && ! $signature->tokenExpired()
                    && filled($signature->approvalUrl())
            )
            ->map(fn (QualityControlSignature $signature): array => [
                'step' => ((int) $signature->step_order) + 1,
                'role_label' => $signature->role_label,
                'signer_name' => $signature->signer_name,
                'status' => $signature->status,
                'status_label' => 'Menunggu TTD',
                'link' => $signature->approvalUrl(),
                'whatsapp_url' => ApprovalWhatsappLink::forQualityControl($signature),
                'expires_at' => $signature->token_expires_at?->format('d/m/Y H:i'),
                'is_expired' => $signature->tokenExpired(),
                'is_active' => $signature->isPending(),
            ])
            ->values()
            ->all();
        $steps = collect([
            [
                'step' => 1,
                'role_label' => 'Pembuat QC',
                'signer_name' => $makerName ?: '-',
                'status' => $makerSigned ? QualityControlSignature::STATUS_SIGNED : QualityControlSignature::STATUS_PENDING,
                'status_label' => $makerSigned ? 'Sudah TTD' : 'Belum TTD',
                'signed_at' => filled($makerSignedAt) ? (string) $makerSignedAt : null,
                'link' => null,
            ],
        ])
            ->merge($approvalSignatures->map(fn (QualityControlSignature $signature): array => [
                'step' => ((int) $signature->step_order) + 1,
                'role_label' => $signature->role_label,
                'signer_name' => $signature->signer_name ?: '-',
                'status' => $signature->status,
                'status_label' => match ($signature->status) {
                    QualityControlSignature::STATUS_SIGNED => 'Sudah TTD',
                    QualityControlSignature::STATUS_PENDING => $signature->tokenExpired() ? 'Token kedaluwarsa' : 'Menunggu TTD',
                    QualityControlSignature::STATUS_LOCKED => 'Belum aktif',
                    QualityControlSignature::STATUS_MISSING => 'Signer belum lengkap',
                    default => ucfirst((string) $signature->status),
                },
                'signed_at' => $signature->signed_at?->format('d/m/Y H:i'),
                'link' => $signature->approvalUrl(),
            ]))
            ->values()
            ->all();

        $isCompleted = $completedSteps >= $totalSteps && $approvalSignatures->isNotEmpty();
        $state = match (true) {
            $isCompleted => 'completed',
            $missingSignature !== null => 'missing',
            $activeSignature !== null && $activeSignature->tokenExpired() => 'expired',
            $activeSignature !== null => 'pending',
            default => 'none',
        };
        $label = match ($state) {
            'completed' => 'Approval QC selesai',
            'missing' => 'Signer QC belum lengkap',
            'expired' => 'Token TTD QC kedaluwarsa',
            'pending' => 'Menunggu TTD QC',
            default => 'Belum ada token aktif',
        };
        $nextText = match (true) {
            $activeSignature !== null => trim(sprintf(
                '%s - %s',
                $activeSignature->role_label,
                $activeSignature->signer_name ?: '-',
            )),
            $missingSignature !== null => trim(sprintf(
                '%s belum ditemukan',
                $missingSignature->role_label,
            )),
            $isCompleted => 'Semua signer QC sudah selesai.',
            default => 'Menunggu token TTD QC dibuat.',
        };
        $timelineDetail = match ($state) {
            'completed' => sprintf('Approval QC selesai (%d/%d).', $completedSteps, $totalSteps),
            'missing' => sprintf('Approval QC %d/%d - %s.', $completedSteps, $totalSteps, $nextText),
            'pending', 'expired' => sprintf('Approval QC %d/%d - menunggu %s.', $completedSteps, $totalSteps, $nextText),
            default => 'Approval QC belum berjalan.',
        };

        return [
            'state' => $state,
            'label' => $label,
            'completed_steps' => $completedSteps,
            'total_steps' => $totalSteps,
            'next_text' => $nextText,
            'timeline_detail' => $timelineDetail,
            'links' => $signatureLinks,
            'steps' => $steps,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveOrderDocumentLink(Order $order, string $type): ?array
    {
        $document = $order->documents
            ->first(fn (OrderDocument $item) => $item->jenis_dokumen?->value === $type);

        if (! $document) {
            return null;
        }

        return [
            'label' => $document->jenis_dokumen?->label() ?? ucfirst(str_replace('_', ' ', $type)),
            'url' => route('user.orders.documents.preview', ['order' => $order, 'document' => $document]),
            'preview_type' => $this->detectPreviewTypeFromFilename($document->nama_file_asli ?: $document->path_file),
        ];
    }

    /**
     * @return array<string, string>|array<string, mixed>|null
     */
    private function resolveHppDocumentLink(Order $order): ?array
    {
        $hpp = $order->latestHpp;

        if (! $hpp) {
            return null;
        }

        $previewType = 'pdf';
        $finalSignedDocument = $hpp->finalSignedDocumentSignature();

        if ($finalSignedDocument?->hasUploadedSignedDocument()) {
            $previewType = $this->detectPreviewTypeFromFilename(
                $finalSignedDocument->signed_document_original_name ?: $finalSignedDocument->signed_document_path
            );
        }

        return [
            'label' => $hpp->hasFinalSignedDocument() ? 'HPP Final DIROPS' : 'HPP PDF',
            'url' => route('user.orders.hpp.pdf', $order),
            'preview_type' => $previewType,
        ];
    }

    /**
     * @return array<string, string>|null
     */
    private function resolveQualityControlDocumentLink(Order $order): ?array
    {
        $report = $order->latestQualityControlReport;

        if (! $report) {
            return null;
        }

        $typeLabel = $report->type === QualityControlReport::TYPE_REFURBISH
            ? 'Refurbish'
            : 'Fabrication';

        return [
            'label' => $report->report_no ?: 'QC '.$typeLabel,
            'url' => route('user.orders.quality-control.pdf', $order),
            'preview_type' => 'pdf',
        ];
    }

    private function detectPreviewTypeFromFilename(?string $filename): string
    {
        $extension = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg' => 'image',
            'pdf' => 'pdf',
            default => 'file',
        };
    }

    private function isWorkshopOnly(Order $order): bool
    {
        return $order->catatan_status === OrderUserNoteStatus::ApprovedWorkshop;
    }

    private function resolveBengkelTask(Order $order): ?BengkelTask
    {
        return BengkelTask::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveBengkelTaskPicAssignments(?BengkelTask $task): array
    {
        if (! $task) {
            return [];
        }

        $profiles = collect($task->person_in_charge_profiles ?? [])
            ->filter(fn ($profile): bool => is_array($profile) && filled($profile['name'] ?? null))
            ->values();

        $picIds = $profiles
            ->pluck('id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $picsById = $picIds->isNotEmpty()
            ? BengkelPic::query()->whereIn('id', $picIds->all())->get()->keyBy('id')
            : collect();

        return $profiles
            ->map(function (array $profile) use ($picsById): array {
                $picId = isset($profile['id']) ? (int) $profile['id'] : null;
                $currentPic = $picId ? $picsById->get($picId) : null;
                $name = (string) ($currentPic?->name ?? $profile['name'] ?? '-');
                $descriptions = collect($profile['work_descriptions'] ?? [])
                    ->map(fn ($description): string => trim((string) $description))
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'name' => $name,
                    'initials' => collect(explode(' ', $name))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                        ->implode('') ?: '?',
                    'avatar_url' => $currentPic?->avatar_url ?? ($profile['avatar_url'] ?? null),
                    'avatar_position' => $currentPic?->avatar_object_position
                        ?? sprintf('%d%% %d%%', (int) ($profile['avatar_position_x'] ?? 50), (int) ($profile['avatar_position_y'] ?? 50)),
                    'work_descriptions' => $descriptions,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveWorkshopPhase(Order $order): string
    {
        $progressStatus = $order->orderWorkshop?->progress_status;

        if ($progressStatus) {
            return OrderWorkshop::progressOptions()[$progressStatus] ?? ucfirst(str_replace('_', ' ', $progressStatus));
        }

        return 'Pekerjaan bengkel diproses';
    }

    private function resolveWorkshopPhaseTone(Order $order): string
    {
        return match ($order->orderWorkshop?->progress_status) {
            OrderWorkshop::PROGRESS_DONE => 'emerald',
            OrderWorkshop::PROGRESS_QUALITY_CONTROL, OrderWorkshop::PROGRESS_IN_PROGRESS => 'blue',
            OrderWorkshop::PROGRESS_PENDING => 'orange',
            OrderWorkshop::PROGRESS_MENUNGGU_JADWAL => 'amber',
            default => 'slate',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveProgress(Order $order): array
    {
        $isEmergency = Order::priorityPrimaryFor($order->prioritas) === 'emergency';
        $initialWork = $order->initialWork;
        $purchaseOrder = $order->purchaseOrder;

        if ($isEmergency && $initialWork) {
            return [
                'percent' => (int) ($initialWork->progress_pekerjaan ?? 0),
                'source' => 'Initial Work',
                'target' => $initialWork->target_penyelesaian?->format('d/m/Y'),
                'note' => $initialWork->admin_note ?: $initialWork->vendor_note,
            ];
        }

        return [
            'percent' => (int) ($purchaseOrder?->progress_pekerjaan ?? 0),
            'source' => 'Purchase Order',
            'target' => $purchaseOrder?->target_penyelesaian?->format('d/m/Y'),
            'note' => $purchaseOrder?->admin_note ?: $purchaseOrder?->vendor_note,
        ];
    }

    private function resolveCurrentPhase(Order $order): string
    {
        $terminOne = $order->lhppBasts->firstWhere('termin_type', 'termin_1');
        $terminTwo = $order->lhppBasts->firstWhere('termin_type', 'termin_2') ?: $terminOne?->terminTwo;
        $terminTwo = (int) ($terminOne?->garansi?->garansi_months ?? -1) === 0 ? null : $terminTwo;

        return match (true) {
            $terminTwo !== null => 'Termin 2 berjalan',
            $terminOne !== null => 'Termin 1 berjalan',
            filled($order->purchaseOrder?->purchase_order_number) => 'PO tersedia',
            $order->budgetVerification !== null => 'Verifikasi anggaran',
            $order->latestHpp !== null => 'HPP tersedia',
            $order->initialWork !== null => 'Initial Work tersedia',
            default => 'Order diproses',
        };
    }

    private function resolveCurrentPhaseTone(Order $order): string
    {
        return match (true) {
            $order->lhppBasts->isNotEmpty() => 'emerald',
            filled($order->purchaseOrder?->purchase_order_number) => 'blue',
            $order->budgetVerification?->status_anggaran === 'Tidak Tersedia' => 'rose',
            $order->budgetVerification?->status_anggaran === 'Tersedia' => 'emerald',
            $order->latestHpp !== null => 'amber',
            default => 'slate',
        };
    }

    private function safeFilename(?string $filename): string
    {
        $filename = trim((string) $filename);

        return $filename !== '' ? str_replace('"', '', $filename) : 'document';
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private function pdfNoCacheHeaders(array $headers = []): array
    {
        return array_merge($headers, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * @param \Illuminate\Support\Collection<int, Order> $orders
     * @return array<string, array<int, int|string|float>>
     */
    private function buildApprovedChartData($orders): array
    {
        $approvedStatuses = [
            OrderUserNoteStatus::ApprovedJasa,
            OrderUserNoteStatus::ApprovedWorkshop,
            OrderUserNoteStatus::ApprovedWorkshopJasa,
        ];

        $grouped = $orders
            ->filter(fn (Order $order) => in_array($order->catatan_status, $approvedStatuses, true))
            ->groupBy(fn (Order $order) => $order->unit_kerja ?: 'Tanpa Unit')
            ->map(fn ($items, $unit) => [
                'label' => (string) $unit,
                'value' => $items->count(),
            ])
            ->sortByDesc('value')
            ->take(10)
            ->values();

        return [
            'labels' => $grouped->pluck('label')->all(),
            'values' => $grouped->pluck('value')->all(),
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, Order> $orders
     * @return array<string, array<int, int|string|float>>
     */
    private function buildBiayaChartData($orders): array
    {
        $grouped = $orders
            ->filter(fn (Order $order) => $order->latestHpp !== null)
            ->groupBy(fn (Order $order) => $order->unit_kerja ?: 'Tanpa Unit')
            ->map(function ($items, $unit): array {
                $total = $items->sum(
                    fn (Order $order) => (float) ($order->latestHpp?->total_keseluruhan ?? 0)
                );

                return [
                    'label' => (string) $unit,
                    'value' => round($total, 2),
                ];
            })
            ->sortByDesc('value')
            ->take(10)
            ->values();

        return [
            'labels' => $grouped->pluck('label')->all(),
            'values' => $grouped->pluck('value')->all(),
        ];
    }

    /**
     * @param array<int, string> $pdfOutputs
     */
    private function mergePdfOutputs(array $pdfOutputs): string
    {
        $pdfOutputs = array_values(array_filter(
            $pdfOutputs,
            static fn ($pdfOutput): bool => is_string($pdfOutput) && trim($pdfOutput) !== ''
        ));

        if ($pdfOutputs === []) {
            return '';
        }

        if (! class_exists(Fpdi::class)) {
            Log::warning('FPDI package is unavailable. Returning the first PDF output without merge.', [
                'controller' => static::class,
                'pdf_count' => count($pdfOutputs),
            ]);

            return $pdfOutputs[0];
        }

        $fpdi = new Fpdi();
        $temporaryFiles = [];

        try {
            foreach ($pdfOutputs as $pdfOutput) {
                if (! is_string($pdfOutput) || trim($pdfOutput) === '') {
                    continue;
                }

                $temporaryFile = tempnam(sys_get_temp_dir(), 'woms-user-pdf-');

                if ($temporaryFile === false) {
                    continue;
                }

                file_put_contents($temporaryFile, $pdfOutput);
                $temporaryFiles[] = $temporaryFile;

                $pageCount = $fpdi->setSourceFile($temporaryFile);

                for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                    $templateId = $fpdi->importPage($pageNumber);
                    $templateSize = $fpdi->getTemplateSize($templateId);
                    $orientation = $templateSize['width'] > $templateSize['height'] ? 'L' : 'P';

                    $fpdi->AddPage($orientation, [$templateSize['width'], $templateSize['height']]);
                    $fpdi->useTemplate($templateId);
                }
            }

            return $fpdi->Output('S');
        } finally {
            foreach ($temporaryFiles as $temporaryFile) {
                if (is_string($temporaryFile) && is_file($temporaryFile)) {
                    @unlink($temporaryFile);
                }
            }
        }
    }
}
