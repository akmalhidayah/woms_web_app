<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\StoreOrderRequest;
use App\Http\Requests\Admin\Orders\UpdateOrderPriorityRequest;
use App\Http\Requests\Admin\Orders\UpdateOrderRequest;
use App\Http\Requests\Admin\Orders\UpdateOrderUserNoteRequest;
use App\Models\Order;
use App\Models\UnitWork;
use App\Http\Controllers\Admin\Orders\InitialWorkController;
use App\Services\Orders\OrderDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderDocumentService $documentService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $seksi = trim((string) $request->string('seksi'));
        $catatanStatus = trim((string) $request->string('catatan_status'));
        $perPage = 10;
        $structureUnits = UnitWork::query()
            ->with(['sections:id,unit_work_id,name'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $orders = Order::query()
            ->with([
                'creator:id,name',
                'documents:id,order_id,jenis_dokumen',
                'scopeOfWork:id,order_id',
                'initialWork:id,order_id,nomor_initial_work,kepada_yth,perihal,tanggal_initial_work,functional_location,scope_pekerjaan,qty,stn,keterangan,keterangan_pekerjaan',
            ])
            ->search($search)
            ->when($seksi !== '', fn ($query) => $query->where('seksi', $seksi))
            ->when($catatanStatus !== '', fn ($query) => $query->where('catatan_status', $catatanStatus))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'search' => $search,
            'selectedSeksi' => $seksi,
            'selectedCatatanStatus' => $catatanStatus,
            'seksiOptions' => Order::query()
                ->select('seksi')
                ->whereNotNull('seksi')
                ->distinct()
                ->orderBy('seksi')
                ->pluck('seksi'),
            'unitKerjaOptions' => Order::query()
                ->select('unit_kerja')
                ->whereNotNull('unit_kerja')
                ->distinct()
                ->orderBy('unit_kerja')
                ->pluck('unit_kerja'),
            'structureUnitOptions' => $structureUnits,
            'userNoteStatusOptions' => OrderUserNoteStatus::options(),
            'userNoteDetailOptions' => Order::userNoteDetailOptions(),
            'initialWorkPreviewNumber' => InitialWorkController::previewDocumentNumber(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.orders.create', $this->formData(new Order([
            'tanggal_order' => now(),
            'target_selesai' => now()->addWeek(),
        ])));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = Order::create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Order pekerjaan berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order): View
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
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order): View
    {
        return view('admin.orders.edit', $this->formData($order));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $order->update($request->validated());

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Order pekerjaan berhasil diperbarui.');
    }

    /**
     * Update the priority only.
     */
    public function updatePriority(UpdateOrderPriorityRequest $request, Order $order): JsonResponse
    {
        $order->update([
            'prioritas' => $request->validated('prioritas'),
        ]);

        return response()->json([
            'message' => 'Prioritas order berhasil diperbarui.',
            'order' => [
                'nomor_order' => $order->nomor_order,
                'prioritas' => $order->prioritas,
                'prioritas_label' => $order->priorityLabel(),
            ],
        ]);
    }

    /**
     * Update the user note status and note.
     */
    public function updateUserNote(UpdateOrderUserNoteRequest $request, Order $order): JsonResponse
    {
        $order->update([
            'catatan_status' => $request->validated('catatan_status'),
            'catatan' => $request->validated('catatan'),
        ]);

        $order->refresh();

        return response()->json([
            'message' => 'Catatan user berhasil diperbarui.',
            'order' => [
                'nomor_order' => $order->nomor_order,
                'catatan_status' => $order->catatan_status?->value,
                'catatan_status_label' => $order->catatan_status?->label(),
                'catatan' => $order->catatan,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order): RedirectResponse
    {
        $order->load('documents');

        foreach ($order->documents as $document) {
            $this->documentService->delete($document);
        }

        $order->delete();

        return redirect()
            ->route('admin.orders.index')
            ->with('status', 'Order pekerjaan berhasil dihapus.');
    }

    /**
     * Build the shared form data.
     *
     * @return array<string, mixed>
     */
    private function formData(Order $order): array
    {
        return [
            'order' => $order,
            'priorityOptions' => Order::priorityOptions(),
        ];
    }
}
