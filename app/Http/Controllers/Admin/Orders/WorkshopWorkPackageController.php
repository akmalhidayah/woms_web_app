<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\StoreWorkshopWorkPackageRequest;
use App\Http\Requests\Admin\Orders\StoreWorkshopWorkPackageBatchRequest;
use App\Http\Requests\Admin\Orders\UpdateWorkshopWorkPackageRequest;
use App\Http\Requests\Admin\Orders\UpdateWorkshopWorkPackageStatusRequest;
use App\Models\BengkelPic;
use App\Models\Order;
use App\Models\WorkshopWorkPackage;
use App\Services\BengkelTasks\WorkshopWorkPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkshopWorkPackageController extends Controller
{
    public function __construct(private readonly WorkshopWorkPackageService $service) {}

    public function index(Order $order): View
    {
        $this->service->assertWorkshopOrder($order);
        $order->load(['orderWorkshop', 'bengkelTasks', 'qualityControlReports', 'workshopHandover', 'workPackages.assignments.pic']);
        $order->workPackages->each(static fn (WorkshopWorkPackage $package): WorkshopWorkPackage => $package->setRelation('order', $order));

        return view('admin.orders.workshop.work-packages', [
            'order' => $order,
            'workPackages' => $order->workPackages,
            'bengkelPics' => BengkelPic::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreWorkshopWorkPackageRequest $request, Order $order): RedirectResponse
    {
        $this->service->create($order, $request->validated(), $request->user()?->id);

        return back()->with('status', "Paket pekerjaan untuk Order {$order->nomor_order} berhasil ditambahkan.");
    }

    public function batch(StoreWorkshopWorkPackageBatchRequest $request, Order $order): RedirectResponse
    {
        $this->service->assertWorkshopOrder($order);
        $packages = $request->input('packages', []);
        if (! is_array($packages) || $packages === []) {
            return back()->withErrors(['packages' => 'Minimal satu paket pekerjaan harus diisi.']);
        }

        $this->service->createBatch($order, array_map(
            static fn ($package): array => is_array($package) ? $package : [],
            $packages,
        ), $request->user()?->id);

        return back()->with('status', count($packages)." paket berhasil dibuat untuk Order {$order->nomor_order}.");
    }

    public function update(UpdateWorkshopWorkPackageRequest $request, Order $order, WorkshopWorkPackage $workPackage): RedirectResponse
    {
        abort_unless((int) $workPackage->order_id === (int) $order->getKey(), 404);
        $this->service->update($workPackage, $request->validated(), $request->user()?->id);

        return back()->with('status', 'Paket pekerjaan berhasil diperbarui.');
    }

    public function updateStatus(UpdateWorkshopWorkPackageStatusRequest $request, WorkshopWorkPackage $workPackage): RedirectResponse
    {
        $this->service->updateStatus($workPackage, $request->validated('status'), $request->validated('pending_reason'), $request->user()?->id);

        return back()->with('status', 'Status paket pekerjaan berhasil diperbarui.');
    }

    public function updateStatusForOrder(UpdateWorkshopWorkPackageStatusRequest $request, Order $order, WorkshopWorkPackage $workPackage): RedirectResponse
    {
        abort_unless((int) $workPackage->order_id === (int) $order->getKey(), 404);
        $this->service->assertWorkshopOrder($order);
        $this->service->updateStatus($workPackage, $request->validated('status'), $request->validated('pending_reason'), $request->user()?->id);

        return back()->with('status', 'Status paket pekerjaan berhasil diperbarui.');
    }

    public function destroy(Order $order, WorkshopWorkPackage $workPackage): RedirectResponse
    {
        abort_unless((int) $workPackage->order_id === (int) $order->getKey(), 404);
        $this->service->delete($workPackage);

        return back()->with('status', 'Paket pekerjaan berhasil dihapus.');
    }
}
