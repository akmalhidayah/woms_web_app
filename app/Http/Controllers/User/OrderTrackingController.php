<?php

namespace App\Http\Controllers\User;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\OrderDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\Response;

class OrderTrackingController extends Controller
{
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
        $signaturePath = null;

        if ($scopeOfWork->tanda_tangan && str_starts_with($scopeOfWork->tanda_tangan, 'data:image')) {
            $signatureDirectory = storage_path('app/public/signatures');

            if (! File::exists($signatureDirectory)) {
                File::makeDirectory($signatureDirectory, 0755, true);
            }

            $imageData = explode(',', $scopeOfWork->tanda_tangan)[1] ?? null;

            if ($imageData) {
                $signaturePath = $signatureDirectory.DIRECTORY_SEPARATOR.'scope-of-work-'.$scopeOfWork->id.'.png';
                file_put_contents($signaturePath, base64_decode($imageData));
            }
        }

        $pdf = Pdf::loadView('admin.orders.scope-of-work-pdf', [
            'order' => $order,
            'scopeOfWork' => $scopeOfWork,
            'scopeItems' => $scopeOfWork->scope_items ?? [],
            'signaturePath' => $signaturePath,
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

        $hpp->loadMissing(['order', 'outlineAgreement', 'creator', 'signatures']);

        $finalSignedDocument = $hpp->finalSignedDocumentSignature();

        if ($finalSignedDocument?->hasUploadedSignedDocument()) {
            abort_unless(Storage::disk('public')->exists($finalSignedDocument->signed_document_path), 404);

            return response()->file(
                Storage::disk('public')->path($finalSignedDocument->signed_document_path),
                [
                    'Content-Type' => $finalSignedDocument->signed_document_mime_type ?: (Storage::disk('public')->mimeType($finalSignedDocument->signed_document_path) ?: 'application/octet-stream'),
                    'Content-Disposition' => 'inline; filename="'.$this->safeFilename(
                        $finalSignedDocument->signed_document_original_name ?: basename($finalSignedDocument->signed_document_path)
                    ).'"',
                ],
            );
        }

        $pdf = Pdf::loadView('admin.hpp.hpppdf', [
            'hpp' => $hpp,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('hpp-'.$hpp->nomor_order.'.pdf');
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

    public function bastPdf(Order $order, string $termin): Response
    {
        $order = $this->ownedOrder($order);
        $terminType = $termin === 'termin-2' ? 'termin_2' : 'termin_1';

        $lhpp = $order->lhppBasts()
            ->where('termin_type', $terminType)
            ->with([
                'images',
                'parentLhppBast.images',
                'parentLhppBast.purchaseOrder:id,order_id,purchase_order_number',
                'parentLhppBast.order.purchaseOrder:id,order_id,purchase_order_number',
                'purchaseOrder:id,order_id,purchase_order_number',
                'order.purchaseOrder:id,order_id,purchase_order_number',
                'hpp.order',
                'hpp.outlineAgreement.unitWork.department',
                'hpp.creator',
            ])
            ->firstOrFail();

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
        $progress = $this->resolveProgress($order);
        $terminOne = $order->lhppBasts->firstWhere('termin_type', 'termin_1');
        $terminTwo = $order->lhppBasts->firstWhere('termin_type', 'termin_2') ?: $terminOne?->terminTwo;
        $garansi = $terminOne?->garansi;
        $isWithoutWarranty = (int) ($garansi?->garansi_months ?? -1) === 0;
        $terminTwo = $isWithoutWarranty ? null : $terminTwo;
        $abnormalitasDocument = $this->resolveOrderDocumentLink($order, 'abnormalitas');

        return [
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_order' => $order->tanggal_order?->format('d/m/Y'),
            'prioritas_label' => $order->priorityLabel(),
            'prioritas_badge_classes' => $order->priorityBadgeClasses(),
            'status_label' => $this->resolveCurrentPhase($order),
            'status_tone' => $this->resolveCurrentPhaseTone($order),
            'progress' => $progress,
            'document_completion_percentage' => $order->documentCompletionPercentage(),
            'show_url' => route('user.orders.show', $order),
            'quick_links' => [
                'abnormalitas' => $abnormalitasDocument['url'] ?? null,
                'hpp' => $order->latestHpp ? route('user.orders.hpp.pdf', $order) : null,
                'bast_termin_1' => $terminOne ? route('user.orders.bast.pdf', ['order' => $order, 'termin' => 'termin-1']) : null,
                'bast_termin_2' => $terminTwo ? route('user.orders.bast.pdf', ['order' => $order, 'termin' => 'termin-2']) : null,
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
        $progress = $this->resolveProgress($order);
        $terminOne = $order->lhppBasts->firstWhere('termin_type', 'termin_1');
        $terminTwo = $order->lhppBasts->firstWhere('termin_type', 'termin_2') ?: $terminOne?->terminTwo;
        $lpjPpl = $terminOne?->lpjPpl;
        $garansi = $terminOne?->garansi;
        $isWithoutWarranty = (int) ($garansi?->garansi_months ?? -1) === 0;
        $terminTwo = $isWithoutWarranty ? null : $terminTwo;
        $hppDocument = $this->resolveHppDocumentLink($order);
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

        return [
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'deskripsi' => $order->deskripsi,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_order' => $order->tanggal_order?->format('d/m/Y'),
            'target_selesai_order' => $order->target_selesai?->format('d/m/Y'),
            'prioritas_label' => $order->priorityLabel(),
            'prioritas_badge_classes' => $order->priorityBadgeClasses(),
            'approval_label' => $order->catatan_status?->label() ?? OrderUserNoteStatus::Pending->label(),
            'approval_note' => $order->catatan,
            'progress' => $progress,
            'timeline' => [
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
            ],
            'documents' => [
                'abnormalitas' => $this->resolveOrderDocumentLink($order, 'abnormalitas'),
                'gambar_teknik' => $this->resolveOrderDocumentLink($order, 'gambar_teknik'),
                'scope_of_work' => $order->scopeOfWork ? route('user.orders.scope-of-work.pdf', $order) : null,
                'initial_work' => $order->initialWork ? route('user.orders.initial-work.pdf', $order) : null,
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

    private function detectPreviewTypeFromFilename(?string $filename): string
    {
        $extension = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg' => 'image',
            'pdf' => 'pdf',
            default => 'file',
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
