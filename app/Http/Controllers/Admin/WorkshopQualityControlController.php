<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BengkelTasks\WorkshopQualityControlQueue;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class WorkshopQualityControlController extends Controller
{
    public function __invoke(Request $request, WorkshopQualityControlQueue $queue): View
    {
        $search = trim((string) $request->string('search'));
        $type = trim((string) $request->string('type'));
        $tab = (string) $request->input('tab', WorkshopQualityControlQueue::ACTION);
        $tab = in_array($tab, ['action', 'process', 'history'], true) ? $tab : WorkshopQualityControlQueue::ACTION;

        $baseQuery = $queue->query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('nomor_order', 'like', "%{$search}%")
                    ->orWhere('nama_pekerjaan', 'like', "%{$search}%")
                    ->orWhere('unit_kerja', 'like', "%{$search}%");
            }))
            ->when($type !== '', fn ($query) => $query->whereHas('latestQualityControlReport', fn ($report) => $report->where('type', $type)))
            ->latest('id');

        $classified = collect();
        foreach ($baseQuery->lazyById(200, 'id') as $order) {
            $state = $queue->status($order);
            $matches = match ($tab) {
                'process' => $state['key'] === 'approval',
                'history' => $state['key'] === 'completed',
                default => (bool) $state['action'],
            };

            if ($matches) {
                $classified->push($order);
            }
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 10;
        $orders = new LengthAwarePaginator(
            $classified->forPage($page, $perPage)->values(),
            $classified->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $counts = ['action' => 0, 'process' => 0, 'history' => 0];
        foreach ($queue->query()->lazyById(200, 'id') as $order) {
            $state = $queue->status($order);
            $counts[$state['key'] === 'completed' ? 'history' : ($state['key'] === 'approval' ? 'process' : 'action')]++;
        }

        return view('admin.workshop-quality-control.index', [
            'orders' => $orders,
            'queue' => $queue,
            'search' => $search,
            'selectedType' => $type,
            'tab' => $tab,
            'tabCounts' => $counts,
            'approvalReassignmentUsers' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'nomor_hp']),
        ]);
    }
}
