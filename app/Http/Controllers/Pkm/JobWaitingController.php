<?php

namespace App\Http\Controllers\Pkm;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pkm\UpdateJobWaitingRequest;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class JobWaitingController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $selectedPriority = trim((string) $request->string('priority'));
            $search = trim((string) $request->string('search'));

            $priorityMap = [
                'Urgently' => Order::PRIORITY_URGENT,
                'Hard' => Order::PRIORITY_HIGH,
                'Medium' => Order::PRIORITY_MEDIUM,
                'Low' => Order::PRIORITY_LOW,
            ];

            $notifications = Order::query()
                ->with([
                    'documents',
                    'scopeOfWork',
                    'lhppBasts:id,order_id,termin_type',
                    'lhppBasts.lpjPpl:id,lhpp_bast_id',
                    'latestHpp' => fn ($query) => $query->select([
                        'hpps.id',
                        'hpps.order_id',
                        'hpps.nomor_order',
                    ]),
                    'latestPurchaseOrder' => fn ($query) => $query->select([
                        'purchase_orders.id',
                        'purchase_orders.order_id',
                        'purchase_orders.hpp_id',
                        'purchase_orders.purchase_order_number',
                        'purchase_orders.target_penyelesaian',
                        'purchase_orders.approval_target',
                        'purchase_orders.progress_pekerjaan',
                        'purchase_orders.vendor_note',
                        'purchase_orders.admin_note',
                        'purchase_orders.po_document_path',
                        'purchase_orders.created_at',
                        'purchase_orders.updated_at',
                    ]),
                ])
                ->whereIn('catatan_status', [
                    OrderUserNoteStatus::ApprovedJasa->value,
                    OrderUserNoteStatus::ApprovedWorkshopJasa->value,
                ])
                ->whereHas('purchaseOrder', function (Builder $query): void {
                    $query
                        ->where('approve_manager', true)
                        ->whereNotNull('purchase_order_number')
                        ->whereRaw("TRIM(purchase_order_number) <> ''");
                })
                ->where(function (Builder $query): void {
                    $query
                        ->doesntHave('latestHpp')
                        ->orWhereDoesntHave('lhppBasts', function (Builder $bastQuery): void {
                            $bastQuery
                                ->where('termin_type', 'termin_1')
                                ->whereHas('garansi')
                                ->whereHas('lpjPpl', function (Builder $lpjPplQuery): void {
                                    $lpjPplQuery
                                        ->whereNotNull('lpj_document_path_termin1')
                                        ->whereNotNull('ppl_document_path_termin1');
                                });
                        });
                })
                ->when($selectedPriority !== '' && isset($priorityMap[$selectedPriority]), function (Builder $query) use ($priorityMap, $selectedPriority): void {
                    $query->where('prioritas', $priorityMap[$selectedPriority]);
                })
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $query->where(function (Builder $builder) use ($search): void {
                        $builder
                            ->where('nomor_order', 'like', "%{$search}%")
                            ->orWhere('notifikasi', 'like', "%{$search}%")
                            ->orWhere('nama_pekerjaan', 'like', "%{$search}%")
                            ->orWhere('unit_kerja', 'like', "%{$search}%")
                            ->orWhere('seksi', 'like', "%{$search}%");
                    });
                })
                ->latest('id')
                ->paginate(8)
                ->withQueryString();

            $notifications->setCollection(
                $notifications->getCollection()->map(fn (Order $order) => $this->mapNotification($order))
            );

            return view('dashboards.pkm', [
                'pageTitle' => 'List Pekerjaan',
                'pageDescription' => 'Daftar pekerjaan PKM yang menunggu tindak lanjut vendor.',
                'notifications' => $notifications,
                'selectedPriority' => $selectedPriority,
                'search' => $search,
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load PKM job waiting page.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat daftar pekerjaan PKM.');
        }
    }

    public function update(UpdateJobWaitingRequest $request, Order $order): RedirectResponse
    {
        try {
            $order->loadMissing('purchaseOrder');

            $purchaseOrder = $order->purchaseOrder;

            abort_unless($purchaseOrder, Response::HTTP_NOT_FOUND);
            abort_unless(
                $purchaseOrder->approve_manager
                && filled($purchaseOrder->purchase_order_number),
                Response::HTTP_FORBIDDEN
            );

            $currentProgress = (int) ($purchaseOrder->progress_pekerjaan ?? 0);
            $nextProgress = $currentProgress;

            if ($request->boolean('start_progress')) {
                $nextProgress = max($currentProgress, 11);
            } elseif ($request->filled('progress_pekerjaan') && $currentProgress >= 11) {
                $nextProgress = max(11, min(100, $request->integer('progress_pekerjaan')));
            }

            $purchaseOrder->fill([
                'progress_pekerjaan' => $nextProgress,
                'target_penyelesaian' => $this->normalizeNullableString($request->input('target_penyelesaian')),
                'vendor_note' => $this->normalizeNullableString($request->input('catatan')),
                'updated_by' => $request->user()?->id,
            ]);

            $purchaseOrder->save();

            return redirect()
                ->route('pkm.jobwaiting', [
                    'priority' => $request->input('_filter_priority'),
                    'search' => $request->input('_filter_search'),
                    'page' => $request->input('_filter_page'),
                ])
                ->with('status', sprintf(
                    $request->boolean('start_progress')
                        ? 'Progress pekerjaan %s berhasil dimulai.'
                        : 'Data pekerjaan %s berhasil diperbarui.',
                    $order->nomor_order,
                ));
        } catch (Throwable $exception) {
            $statusCode = $this->resolveStatusCode($exception);

            Log::error('Failed to update PKM job waiting data.', [
                'status_code' => $statusCode,
                'user_id' => $request->user()?->id,
                'order_id' => $order->id,
                'nomor_order' => $order->nomor_order,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            if ($statusCode !== Response::HTTP_INTERNAL_SERVER_ERROR) {
                throw $exception;
            }

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat menyimpan data pekerjaan PKM.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mapNotification(Order $order): array
    {
        $latestHpp = $order->latestHpp;
        $latestPurchaseOrder = $order->latestPurchaseOrder;
        $abnormalDocument = $this->findDocument($order, OrderDocumentType::Abnormalitas);
        $gambarDocument = $this->findDocument($order, OrderDocumentType::GambarTeknik);
        $terminOneBast = $order->lhppBasts->firstWhere('termin_type', 'termin_1');
        $hasBastOrLpj = (bool) $terminOneBast || (bool) optional($terminOneBast)->lpjPpl;
        $isFinished = (int) ($latestPurchaseOrder?->progress_pekerjaan ?? 0) >= 100 && $hasBastOrLpj;

        return [
            'nomor_order' => $order->nomor_order,
            'notification_number' => $order->notifikasi,
            'jobwaiting_since_raw' => $latestPurchaseOrder?->updated_at?->toDateString()
                ?: $latestPurchaseOrder?->created_at?->toDateString(),
            'jobwaiting_since' => $latestPurchaseOrder?->updated_at?->format('d/m/Y')
                ?: $latestPurchaseOrder?->created_at?->format('d/m/Y'),
            'priority' => $this->priorityLabel($order->prioritas),
            'job_name' => $order->nama_pekerjaan,
            'seksi' => $order->seksi,
            'unit' => $order->unit_kerja,
            'progress' => (int) ($latestPurchaseOrder?->progress_pekerjaan ?? 0),
            'target_penyelesaian' => $latestPurchaseOrder?->target_penyelesaian?->format('Y-m-d'),
            'approval_target' => $latestPurchaseOrder?->approval_target,
            'catatan' => $latestPurchaseOrder?->vendor_note ?: ($order->catatan ?: ''),
            'catatan_admin' => $latestPurchaseOrder?->admin_note ?: 'Belum ada catatan dari Admin Bengkel.',
            'is_finished' => $isFinished,
            'documents' => [
                [
                    'label' => 'Abnormalitas',
                    'icon' => 'alert-circle',
                    'tone' => 'rose',
                    'ready' => (bool) $abnormalDocument,
                    'url' => $abnormalDocument ? route('pkm.jobwaiting.documents.preview', ['order' => $order, 'document' => $abnormalDocument]) : null,
                ],
                [
                    'label' => 'Scope of Work',
                    'icon' => 'clipboard-list',
                    'tone' => 'emerald',
                    'ready' => (bool) $order->scopeOfWork,
                    'url' => $order->scopeOfWork ? route('pkm.jobwaiting.scope-of-work.pdf', ['order' => $order, 'scopeOfWork' => $order->scopeOfWork]) : null,
                ],
                [
                    'label' => 'Gambar Teknik',
                    'icon' => 'image',
                    'tone' => 'blue',
                    'ready' => (bool) $gambarDocument,
                    'url' => $gambarDocument ? route('pkm.jobwaiting.documents.preview', ['order' => $order, 'document' => $gambarDocument]) : null,
                ],
                [
                    'label' => 'HPP',
                    'icon' => 'file-text',
                    'tone' => 'orange',
                    'ready' => (bool) $latestHpp,
                    'url' => $latestHpp ? route('pkm.jobwaiting.hpp.pdf', ['hpp' => $latestHpp->nomor_order]) : null,
                ],
                [
                    'label' => $latestPurchaseOrder?->purchase_order_number
                        ? 'PO : '.$latestPurchaseOrder->purchase_order_number
                        : 'PO',
                    'icon' => 'receipt',
                    'tone' => 'indigo',
                    'ready' => (bool) ($latestHpp && $latestPurchaseOrder?->po_document_path),
                    'url' => ($latestHpp && $latestPurchaseOrder?->po_document_path)
                        ? route('pkm.jobwaiting.purchase-order.document', ['hpp' => $latestHpp->nomor_order])
                        : null,
                ],
                [
                    'label' => 'Initial Work',
                    'icon' => 'file-badge',
                    'tone' => 'violet',
                    'ready' => false,
                    'url' => null,
                ],
            ],
        ];
    }

    private function findDocument(Order $order, OrderDocumentType $type): mixed
    {
        return $order->documents->first(
            fn ($document) => $document->jenis_dokumen === $type
        );
    }

    private function priorityLabel(?string $priority): string
    {
        return match ($priority) {
            Order::PRIORITY_URGENT => 'Urgently',
            Order::PRIORITY_HIGH => 'Hard',
            Order::PRIORITY_MEDIUM => 'Medium',
            Order::PRIORITY_LOW => 'Low',
            default => 'Low',
        };
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function resolveStatusCode(Throwable $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            $statusCode = (int) $exception->getStatusCode();

            if ($statusCode >= 100 && $statusCode <= 599) {
                return $statusCode;
            }
        }

        $code = (int) $exception->getCode();

        if ($code >= 100 && $code <= 599) {
            return $code;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
