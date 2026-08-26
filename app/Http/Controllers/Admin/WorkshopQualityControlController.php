<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\BengkelTasks\WorkshopQualityControlQueue;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkshopQualityControlController extends Controller
{
    public function __invoke(Request $request, WorkshopQualityControlQueue $queue): View
    {
        $search = trim((string) $request->string('search'));
        $type = trim((string) $request->string('type'));
        $status = trim((string) $request->string('status', WorkshopQualityControlQueue::ACTION));

        $orders = $queue->query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('nomor_order', 'like', "%{$search}%")
                    ->orWhere('nama_pekerjaan', 'like', "%{$search}%")
                    ->orWhere('unit_kerja', 'like', "%{$search}%");
            }))
            ->when($type !== '', fn ($query) => $query->whereHas('latestQualityControlReport', fn ($report) => $report->where('type', $type)))
            ->latest('id')
            ->get()
            ->filter(function (Order $order) use ($queue, $status): bool {
                $resolved = $queue->status($order);

                return $status === '' || ($status === WorkshopQualityControlQueue::ACTION ? $resolved['action'] : $resolved['key'] === $status);
            })
            ->values();

        return view('admin.workshop-quality-control.index', [
            'orders' => $orders,
            'queue' => $queue,
            'search' => $search,
            'selectedType' => $type,
            'selectedStatus' => $status,
            'approvalReassignmentUsers' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'nomor_hp']),
        ]);
    }
}
