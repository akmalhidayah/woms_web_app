<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\StoreOrderRequest;
use App\Http\Requests\Admin\Orders\UpdateOrderWorkshopRequest;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\QualityControlReport;
use App\Models\UnitWork;
use App\Models\User;
use App\Services\BengkelTasks\WorkshopOrderTaskSyncer;
use App\Services\BengkelTasks\WorkshopWorkPackageService;
use App\Support\WorkshopReadiness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderWorkshopController extends Controller
{
    public function __construct(
        private readonly WorkshopOrderTaskSyncer $workshopOrderTaskSyncer,
        private readonly WorkshopReadiness $workshopReadiness,
        private readonly WorkshopWorkPackageService $workPackageService,
    ) {}

    public function index(Request $request): View
    {
        $activeTab = in_array($request->string('tab')->toString(), ['action', 'history'], true)
            ? $request->string('tab')->toString()
            : 'action';
        $search = trim((string) $request->string('search'));
        $progress = trim((string) $request->string('progress'));
        $regu = trim((string) $request->string('regu'));
        $readiness = trim((string) $request->string('readiness'));
        $perPage = 10;

        if ($activeTab === 'history' || $progress === OrderWorkshop::PROGRESS_DONE) {
            $progress = '';
            $readiness = '';
        }

        $baseQuery = Order::query()
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedWorkshop->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ]);

        $tabCounts = [
            'action' => (clone $baseQuery)->whereDoesntHave(
                'orderWorkshop',
                fn ($builder) => $builder->where('progress_status', OrderWorkshop::PROGRESS_DONE),
            )->count(),
            'history' => (clone $baseQuery)->whereHas(
                'orderWorkshop',
                fn ($builder) => $builder->where('progress_status', OrderWorkshop::PROGRESS_DONE),
            )->count(),
        ];

        $orders = (clone $baseQuery)
            ->with([
                'documents:id,order_id,jenis_dokumen,nama_file_asli',
                'scopeOfWork:id,order_id',
                'orderWorkshop',
                'workPackages.assignments',
                'bengkelTasks:id,order_id,notification_number,job_name,person_in_charge,person_in_charge_profiles,archived_at',
                'workshopHandover:id,order_id,status',
                'latestQualityControlReport.signatures',
                'latestQualityControlReport.signatures.signer:id,name,email,nomor_hp',
            ])
            ->when(
                $activeTab === 'history',
                fn ($query) => $query->whereHas(
                    'orderWorkshop',
                    fn ($builder) => $builder->where('progress_status', OrderWorkshop::PROGRESS_DONE),
                ),
                fn ($query) => $query->whereDoesntHave(
                    'orderWorkshop',
                    fn ($builder) => $builder->where('progress_status', OrderWorkshop::PROGRESS_DONE),
                ),
            )
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('nomor_order', 'like', "%{$search}%")
                        ->orWhere('nama_pekerjaan', 'like', "%{$search}%")
                        ->orWhere('unit_kerja', 'like', "%{$search}%")
                        ->orWhere('seksi', 'like', "%{$search}%")
                        ->orWhereHas('workPackages', fn ($package) => $package
                            ->where('display_no', 'like', "%{$search}%")
                            ->orWhere('job_name', 'like', "%{$search}%"));
                });
            })
            ->when($progress !== '', fn ($query) => $query->whereHas(
                'orderWorkshop',
                fn ($builder) => $builder->where('progress_status', $progress),
            ))
            ->when($regu !== '', fn ($query) => $query->where('catatan', $regu))
            ->when($readiness === 'incomplete', fn ($query) => $this->workshopReadiness->applyIncomplete($query))
            ->orderByDesc('tanggal_order')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.orders.workshop.index', [
            'orders' => $orders,
            'activeTab' => $activeTab,
            'tabCounts' => $tabCounts,
            'search' => $search,
            'selectedProgress' => $progress,
            'selectedRegu' => $regu,
            'selectedReadiness' => $readiness,
            'progressOptions' => OrderWorkshop::progressOptions(),
            'preparationOptions' => OrderWorkshop::preparationOptions(),
            'reguOptions' => Order::workshopReguOptions(),
            'structureUnitOptions' => UnitWork::query()
                ->with(['sections:id,unit_work_id,name'])
                ->orderBy('name')
                ->get(['id', 'name']),
            'userNoteStatusOptions' => OrderUserNoteStatus::options(),
            'userNoteDetailOptions' => Order::userNoteDetailOptions(),
            'approvalReassignmentUsers' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'nomor_hp']),
            'workshopReadiness' => $this->workshopReadiness,
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $order = DB::transaction(function () use ($request, $validated): Order {
            $order = Order::create([
                ...$validated,
                'biaya' => $validated['biaya'] ?? null,
                'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
                'created_by' => $request->user()?->id,
            ]);

            $workshop = $order->orderWorkshop()->create([
                'progress_status' => OrderWorkshop::PROGRESS_MENUNGGU_JADWAL,
                'started_at' => null,
                'catatan' => $order->catatan,
            ]);
            $this->workshopOrderTaskSyncer->syncOrder($order, $workshop);

            return $order;
        });

        return redirect()
            ->route('admin.orders.workshop.index', ['search' => $order->nomor_order])
            ->with('status', 'Order pekerjaan bengkel berhasil dibuat.')
            ->with('work_package_order', [
                'nomor_order' => $order->nomor_order,
                'url' => route('admin.orders.workshop.work-packages.index', $order),
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

        $validated = $request->validated();

        return DB::transaction(function () use ($order, $validated): JsonResponse {
            // order_workshops is the InnoDB mutex; orders may be MyISAM in production.
            $workshop = $order->orderWorkshop()->lockForUpdate()->firstOrNew();
            $lockedOrder = $order->fresh([
                'orderWorkshop',
                'workPackages',
                'qualityControlReports.signatures',
                'workshopHandover',
            ]) ?: $order;
            $requestedProgress = (string) ($validated['progress_status'] ?? $workshop->progress_status ?? '');

            if (array_key_exists('progress_status', $validated)) {
                $this->assertProgressStartInvariant($workshop, $requestedProgress);
            }

            if ((array_key_exists('preparation_status', $validated) || array_key_exists('preparation_note', $validated))
                && $this->workshopReadiness->preparationLocked(
                    $lockedOrder->orderWorkshop,
                    $lockedOrder->qualityControlReports->isNotEmpty(),
                    $lockedOrder->workshopHandover !== null,
                )) {
                throw ValidationException::withMessages([
                    'preparation_status' => 'Persiapan Order terkunci karena proses Quality Control, Selesai, atau Serah Terima sudah dimulai.',
                ]);
            }

            if (array_key_exists('progress_status', $validated)
                && $requestedProgress === OrderWorkshop::PROGRESS_QUALITY_CONTROL
                && $lockedOrder->isEstimatorWorkshopRegu()) {
                throw ValidationException::withMessages([
                    'progress_status' => 'Regu Estimator merupakan pekerjaan non-critical dan tidak menggunakan Quality Control.',
                ]);
            }

            if (in_array($requestedProgress, [OrderWorkshop::PROGRESS_QUALITY_CONTROL, OrderWorkshop::PROGRESS_DONE], true)) {
                $this->workPackageService->assertParentMayAdvance($lockedOrder);
            }
            $candidate = clone $workshop;
            $candidate->fill(collect($validated)->map(fn ($value) => $value === '' ? null : $value)->all());

            if (array_key_exists('progress_status', $validated)
                && $this->workshopReadiness->requiresReadiness($requestedProgress)
                && $workshop->progress_status !== OrderWorkshop::PROGRESS_DONE
                && ! $this->workshopReadiness->canAdvance($this->readinessCandidate($candidate))) {
                throw ValidationException::withMessages([
                    'progress_status' => 'Persiapan Order harus diselesaikan sebelum progress dapat dilanjutkan ke Quality Control atau Selesai.',
                ]);
            }

            if ($requestedProgress === OrderWorkshop::PROGRESS_DONE
                && $lockedOrder->qualityControlReports->isNotEmpty()
                && ! $lockedOrder->qualityControlReports->contains(fn (QualityControlReport $report): bool => $report->approvalCompleted())) {
                throw ValidationException::withMessages([
                    'progress_status' => 'Proses Quality Control harus diselesaikan sebelum pekerjaan dapat ditandai selesai.',
                ]);
            }

            foreach ($validated as $field => $value) {
                $workshop->{$field} = $value === '' ? null : $value;
            }

            $order->orderWorkshop()->save($workshop);
            $this->workshopOrderTaskSyncer->syncOrder($order->fresh('orderWorkshop'), $workshop->fresh());
            $this->syncBengkelTaskProgress($order, $workshop);

            return response()->json([
                'message' => 'Status order bengkel berhasil diperbarui.',
                'updated' => $workshop->fresh()->toArray(),
            ]);
        });
    }

    public function start(Order $order): JsonResponse
    {
        return DB::transaction(function () use ($order): JsonResponse {
            if (! in_array($order->catatan_status?->value, [
                OrderUserNoteStatus::ApprovedWorkshop->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ], true)) {
                throw ValidationException::withMessages([
                    'progress_status' => 'Order ini tidak termasuk Order Pekerjaan Bengkel.',
                ]);
            }

            $workshop = OrderWorkshop::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (! $workshop) {
                throw ValidationException::withMessages([
                    'progress_status' => 'Data Order Pekerjaan Bengkel tidak ditemukan.',
                ]);
            }

            if ($workshop->started_at !== null) {
                return response()->json([
                    'message' => 'Pekerjaan sudah dimulai.',
                    'updated' => $workshop->toArray(),
                ]);
            }

            if ($workshop->progress_status !== OrderWorkshop::PROGRESS_MENUNGGU_JADWAL) {
                throw ValidationException::withMessages([
                    'progress_status' => 'Pekerjaan legacy sudah berjalan, tetapi waktu mulai belum tercatat.',
                ]);
            }

            $workshop->started_at = now();
            $workshop->progress_status = OrderWorkshop::PROGRESS_IN_PROGRESS;
            $workshop->save();

            $this->workshopOrderTaskSyncer->syncOrder($order->fresh('orderWorkshop') ?: $order, $workshop);
            $this->syncBengkelTaskProgress($order, $workshop);

            return response()->json([
                'message' => 'Pekerjaan berhasil dimulai.',
                'updated' => $workshop->fresh()->toArray(),
            ]);
        });
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

    private function assertProgressStartInvariant(OrderWorkshop $workshop, string $requestedProgress): void
    {
        $currentProgress = $workshop->progress_status ?: OrderWorkshop::PROGRESS_MENUNGGU_JADWAL;

        if ($currentProgress === OrderWorkshop::PROGRESS_MENUNGGU_JADWAL
            && $workshop->started_at === null
            && $requestedProgress !== OrderWorkshop::PROGRESS_MENUNGGU_JADWAL) {
            throw ValidationException::withMessages([
                'progress_status' => 'Klik Start Pekerjaan sebelum mengubah Progress Pekerjaan.',
            ]);
        }

        if ($currentProgress !== OrderWorkshop::PROGRESS_MENUNGGU_JADWAL
            && $requestedProgress === OrderWorkshop::PROGRESS_MENUNGGU_JADWAL) {
            throw ValidationException::withMessages([
                'progress_status' => 'Progress pekerjaan yang sudah berjalan tidak dapat dikembalikan ke Menunggu Jadwal.',
            ]);
        }
    }

    private function readinessCandidate(OrderWorkshop $workshop): OrderWorkshop
    {
        $candidate = clone $workshop;
        $candidate->progress_status = null;

        return $candidate;
    }
}
