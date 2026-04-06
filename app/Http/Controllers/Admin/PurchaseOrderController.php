<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $search = trim((string) $request->string('search'));
            $status = trim((string) $request->string('status'));
            $unit = trim((string) $request->string('unit'));
            $from = trim((string) $request->string('from'));
            $to = trim((string) $request->string('to'));
            $perPage = 10;

            $notifications = Hpp::query()
                ->with([
                    'order:id,nomor_order,unit_kerja,seksi',
                    'budgetVerification:id,order_id,hpp_id,status_anggaran',
                    'purchaseOrder:id,order_id,hpp_id,purchase_order_number,target_penyelesaian,approval_target,approval_note,approve_manager,approve_senior_manager,approve_general_manager,approve_direktur_operasional,progress_pekerjaan,po_document_path,vendor_note,admin_note',
                ])
                ->whereHas('budgetVerification', fn (Builder $query) => $query->where('status_anggaran', BudgetVerification::statusAnggaranOptions()['Tersedia']))
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $query->where(function (Builder $builder) use ($search): void {
                        $builder
                            ->where('nomor_order', 'like', "%{$search}%")
                            ->orWhere('nama_pekerjaan', 'like', "%{$search}%")
                            ->orWhere('unit_kerja', 'like', "%{$search}%")
                            ->orWhereHas('purchaseOrder', fn (Builder $purchaseOrderQuery) => $purchaseOrderQuery->where('purchase_order_number', 'like', "%{$search}%"));
                    });
                })
                ->when($status !== '', fn (Builder $query) => $query->whereHas('purchaseOrder', fn (Builder $purchaseOrderQuery) => $purchaseOrderQuery->where('approval_target', $status)))
                ->when($unit !== '', fn (Builder $query) => $query->where('unit_kerja', $unit))
                ->when($from !== '', fn (Builder $query) => $query->whereHas('purchaseOrder', fn (Builder $purchaseOrderQuery) => $purchaseOrderQuery->whereDate('target_penyelesaian', '>=', $from)))
                ->when($to !== '', fn (Builder $query) => $query->whereHas('purchaseOrder', fn (Builder $purchaseOrderQuery) => $purchaseOrderQuery->whereDate('target_penyelesaian', '<=', $to)))
                ->latest('id')
                ->paginate($perPage)
                ->withQueryString();

            $notifications->setCollection(
                $notifications->getCollection()->map(fn (Hpp $hpp) => $this->mapRow($hpp))
            );

            $units = Hpp::query()
                ->whereHas('budgetVerification', fn (Builder $query) => $query->where('status_anggaran', 'Tersedia'))
                ->select('unit_kerja')
                ->distinct()
                ->orderBy('unit_kerja')
                ->pluck('unit_kerja')
                ->filter()
                ->values()
                ->all();

            return view('admin.purchase-order.index', [
                'notifications' => $notifications,
                'units' => $units,
                'search' => $search,
                'selectedStatus' => $status,
                'selectedUnit' => $unit,
                'selectedFrom' => $from,
                'selectedTo' => $to,
                'statusOptions' => [
                    'setuju' => 'Disetujui',
                    'tidak_setuju' => 'Ditolak',
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load purchase order index.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat data Purchase Order.');
        }
    }

    public function update(UpdatePurchaseOrderRequest $request, Hpp $hpp): RedirectResponse
    {
        try {
            $hpp->loadMissing(['order', 'budgetVerification', 'purchaseOrder']);

            abort_unless($hpp->budgetVerification?->status_anggaran === 'Tersedia', Response::HTTP_FORBIDDEN);

            $purchaseOrder = $hpp->purchaseOrder ?? new PurchaseOrder();

            if (! $purchaseOrder->exists) {
                $purchaseOrder->hpp()->associate($hpp);
                $purchaseOrder->order()->associate($hpp->order);
                $purchaseOrder->created_by = $request->user()?->id;
            }

            $purchaseOrder->fill([
                'purchase_order_number' => $this->normalizeNullableString($request->input('purchase_order_number')),
                'target_penyelesaian' => $this->normalizeNullableString($request->input('target_penyelesaian')),
                'approval_target' => $this->normalizeNullableString($request->input('approval_target')),
                'approve_manager' => $request->boolean('approve_manager'),
                'approve_senior_manager' => $request->boolean('approve_senior_manager'),
                'approve_general_manager' => $request->boolean('approve_general_manager'),
                'approve_direktur_operasional' => $request->boolean('approve_direktur_operasional'),
                'admin_note' => $this->normalizeNullableString($request->input('admin_note')),
            ]);

            if ($request->hasFile('po_document')) {
                if ($purchaseOrder->po_document_path) {
                    Storage::disk('public')->delete($purchaseOrder->po_document_path);
                }

                $directory = $this->buildPoDocumentDirectory($hpp->nomor_order);
                $filename = $this->buildPoDocumentFilename(
                    $request->input('purchase_order_number'),
                    $hpp->nomor_order,
                    $request->file('po_document')->getClientOriginalExtension(),
                );

                $purchaseOrder->po_document_path = $request->file('po_document')->storeAs(
                    $directory,
                    $filename,
                    'public',
                );
            }

            $purchaseOrder->updated_by = $request->user()?->id;
            $purchaseOrder->save();

            return redirect()
                ->route('admin.purchase-order.index', [
                    'search' => $request->input('_filter_search'),
                    'status' => $request->input('_filter_status'),
                    'unit' => $request->input('_filter_unit'),
                    'from' => $request->input('_filter_from'),
                    'to' => $request->input('_filter_to'),
                    'page' => $request->input('_filter_page'),
                ])
                ->with('status', sprintf(
                    'Purchase Order untuk order %s berhasil diperbarui.',
                    $hpp->nomor_order,
                ));
        } catch (Throwable $exception) {
            $statusCode = $this->resolveStatusCode($exception);

            Log::error('Failed to update purchase order.', [
                'status_code' => $statusCode,
                'hpp_id' => $hpp->id,
                'nomor_order' => $hpp->nomor_order,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            if ($statusCode !== Response::HTTP_INTERNAL_SERVER_ERROR) {
                throw $exception;
            }

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat menyimpan Purchase Order.');
        }
    }

    public function document(Hpp $hpp): Response
    {
        try {
            $hpp->loadMissing('purchaseOrder');

            $path = $hpp->purchaseOrder?->po_document_path;

            abort_unless($path && Storage::disk('public')->exists($path), Response::HTTP_NOT_FOUND);

            return response()->file(
                Storage::disk('public')->path($path),
                [
                    'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
                    'Content-Disposition' => 'inline; filename="'.$this->buildPoDocumentFilename(
                        $hpp->purchaseOrder?->purchase_order_number,
                        $hpp->nomor_order,
                        pathinfo($path, PATHINFO_EXTENSION),
                    ).'"',
                ],
            );
        } catch (Throwable $exception) {
            $statusCode = $this->resolveStatusCode($exception);

            Log::error('Failed to open purchase order document.', [
                'status_code' => $statusCode,
                'hpp_id' => $hpp->id,
                'nomor_order' => $hpp->nomor_order,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            if ($statusCode !== Response::HTTP_INTERNAL_SERVER_ERROR) {
                throw $exception;
            }

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat membuka dokumen Purchase Order.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRow(Hpp $hpp): array
    {
        $purchaseOrder = $hpp->purchaseOrder;
        $order = $hpp->order;

        return [
            'nomor_order' => $hpp->nomor_order,
            'nomor_po' => $purchaseOrder?->purchase_order_number,
            'nama_pekerjaan' => $hpp->nama_pekerjaan,
            'unit' => $hpp->unit_kerja,
            'seksi' => $order?->seksi,
            'target_penyelesaian' => $purchaseOrder?->target_penyelesaian?->format('Y-m-d'),
            'approval_target' => $purchaseOrder?->approval_target,
            'approval_note' => $purchaseOrder?->approval_note,
            'approvals' => [
                'manager' => (bool) $purchaseOrder?->approve_manager,
                'senior_manager' => (bool) $purchaseOrder?->approve_senior_manager,
                'general_manager' => (bool) $purchaseOrder?->approve_general_manager,
                'direktur_operasional' => (bool) $purchaseOrder?->approve_direktur_operasional,
            ],
            'progress' => (int) ($purchaseOrder?->progress_pekerjaan ?? 0),
            'po_document_name' => $purchaseOrder?->po_document_path
                ? $this->buildPoDocumentFilename(
                    $purchaseOrder?->purchase_order_number,
                    $hpp->nomor_order,
                    pathinfo($purchaseOrder->po_document_path, PATHINFO_EXTENSION),
                )
                : null,
            'po_document_url' => $purchaseOrder?->po_document_path ? route('admin.purchase-order.document', ['hpp' => $hpp->nomor_order]) : null,
            'vendor_note' => $purchaseOrder?->vendor_note ?: $order?->catatan,
            'admin_note' => $purchaseOrder?->admin_note,
            'update_url' => route('admin.purchase-order.update', ['hpp' => $hpp->nomor_order]),
        ];
    }

    private function buildPoDocumentDirectory(string $nomorOrder): string
    {
        return 'purchase-orders/'.$nomorOrder;
    }

    private function buildPoDocumentFilename(?string $purchaseOrderNumber, string $nomorOrder, ?string $extension = null): string
    {
        $poNumber = $this->normalizePoDocumentSegment($purchaseOrderNumber ?: $nomorOrder);
        $normalizedExtension = trim((string) $extension) !== '' ? strtolower((string) $extension) : 'pdf';

        return 'PO-'.$poNumber.'.'.$normalizedExtension;
    }

    private function normalizePoDocumentSegment(string $value): string
    {
        $normalized = Str::upper(trim($value));
        $normalized = preg_replace('/[^A-Z0-9]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : 'DRAFT';
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
