<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\UpdateOrderWorkshopRequest;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\UnitWork;
use App\Services\QualityControl\QualityControlSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderWorkshopController extends Controller
{
    public function __construct(
        private readonly QualityControlSignatureService $qualityControlSignatureService,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $progress = trim((string) $request->string('progress'));
        $regu = trim((string) $request->string('regu'));
        $perPage = max(10, min((int) $request->integer('perPage', 10), 50));

        $orders = Order::query()
            ->with([
                'documents:id,order_id,jenis_dokumen,nama_file_asli',
                'scopeOfWork:id,order_id',
                'orderWorkshop',
                'latestQualityControlReport.signatures',
            ])
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedWorkshop->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('nomor_order', 'like', "%{$search}%")
                        ->orWhere('nama_pekerjaan', 'like', "%{$search}%")
                        ->orWhere('unit_kerja', 'like', "%{$search}%")
                        ->orWhere('seksi', 'like', "%{$search}%");
                });
            })
            ->when($progress !== '', function ($query) use ($progress) {
                $query->whereHas('orderWorkshop', fn ($builder) => $builder->where('progress_status', $progress));
            })
            ->when($regu !== '', fn ($query) => $query->where('catatan', $regu))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $orders->getCollection()->each(function (Order $order): void {
            if ($order->latestQualityControlReport) {
                $this->qualityControlSignatureService->ensureSignatureChain($order->latestQualityControlReport);
                $order->latestQualityControlReport->load('signatures');
            }
        });

        return view('admin.orders.workshop.index', [
            'orders' => $orders,
            'search' => $search,
            'selectedProgress' => $progress,
            'selectedRegu' => $regu,
            'selectedPerPage' => $perPage,
            'progressOptions' => OrderWorkshop::progressOptions(),
            'materialOptions' => OrderWorkshop::materialOptions(),
            'konfirmasiOptions' => OrderWorkshop::konfirmasiAnggaranOptions(),
            'statusAnggaranOptions' => OrderWorkshop::statusAnggaranOptions(),
            'eKorinStatusOptions' => OrderWorkshop::eKorinStatusOptions(),
            'reguOptions' => [
                'Regu Fabrikasi',
                'Regu Bengkel (Refurbish)',
            ],
            'structureUnitOptions' => UnitWork::query()
                ->with(['sections:id,unit_work_id,name'])
                ->orderBy('name')
                ->get(['id', 'name']),
            'userNoteStatusOptions' => OrderUserNoteStatus::options(),
            'userNoteDetailOptions' => Order::userNoteDetailOptions(),
        ]);
    }

    public function update(UpdateOrderWorkshopRequest $request, Order $order): JsonResponse
    {
        if (! in_array($order->catatan_status?->value, [
            OrderUserNoteStatus::ApprovedWorkshop->value,
            OrderUserNoteStatus::ApprovedWorkshopJasa->value,
        ], true)) {
            return response()->json([
                'error' => 'Order ini tidak termasuk order pekerjaan bengkel.',
            ], 422);
        }

        $workshop = $order->orderWorkshop()->firstOrNew();
        $validated = $request->validated();

        foreach ($validated as $field => $value) {
            $workshop->{$field} = $value === '' ? null : $value;
        }

        if (($workshop->konfirmasi_anggaran ?? null) === OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY) {
            $workshop->status_material = null;
            $workshop->keterangan_material = null;
        } elseif (($workshop->konfirmasi_anggaran ?? null) === OrderWorkshop::KONFIRMASI_MATERIAL_READY) {
            $workshop->status_anggaran = null;
            $workshop->keterangan_anggaran = null;
            $workshop->nomor_e_korin = null;
            $workshop->status_e_korin = null;
        } else {
            $workshop->status_anggaran = null;
            $workshop->keterangan_anggaran = null;
            $workshop->status_material = null;
            $workshop->keterangan_material = null;
            $workshop->progress_status = null;
            $workshop->keterangan_progress = null;
            $workshop->nomor_e_korin = null;
            $workshop->status_e_korin = null;
        }

        $order->orderWorkshop()->save($workshop);
        $this->syncBengkelTaskProgress($order, $workshop);

        return response()->json([
            'message' => 'Status order bengkel berhasil diperbarui.',
            'updated' => $workshop->fresh()->toArray(),
        ]);
    }

    private function syncBengkelTaskProgress(Order $order, OrderWorkshop $workshop): void
    {
        $progressStatus = $workshop->progress_status;

        if (! $progressStatus) {
            return;
        }

        BengkelTask::query()
            ->where('order_id', $order->id)
            ->update([
                'progress_status' => $progressStatus,
                'is_completed' => $progressStatus === OrderWorkshop::PROGRESS_DONE,
                'pending_reason' => $progressStatus === OrderWorkshop::PROGRESS_PENDING
                    ? $workshop->keterangan_progress
                    : null,
            ]);
    }
}
