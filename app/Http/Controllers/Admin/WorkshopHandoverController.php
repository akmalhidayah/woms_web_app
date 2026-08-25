<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BengkelTasks\WorkshopHandoverQueue;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkshopHandoverController extends Controller
{
    public function __invoke(Request $request, WorkshopHandoverQueue $queue): View
    {
        $search = trim((string) $request->string('search'));
        $path = trim((string) $request->string('path'));
        $handovers = $queue->query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('nomor_order', 'like', "%{$search}%")
                    ->orWhere('nama_pekerjaan', 'like', "%{$search}%")
                    ->orWhere('unit_kerja', 'like', "%{$search}%");
            }))
            ->latest('id')
            ->get()
            ->filter(fn ($order): bool => $path === '' || $queue->path($order) === $path)
            ->values();

        return view('admin.workshop-handover.index', compact('handovers', 'queue', 'search', 'path'));
    }
}
