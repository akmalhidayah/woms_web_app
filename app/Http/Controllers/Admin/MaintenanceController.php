<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Maintenance\RunMaintenanceScan;
use App\Services\Maintenance\MaintenanceSnapshotRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(Request $request, MaintenanceSnapshotRepository $snapshots): View
    {
        $tab = $request->string('tab')->toString();
        $allowedTabs = ['summary', 'approval', 'documents', 'files', 'users_structure', 'queue_scheduler'];

        return view('admin.maintenance.index', [
            'quickSnapshot' => $snapshots->snapshot('quick'),
            'deepSnapshot' => $snapshots->snapshot('deep'),
            'scanStatus' => $snapshots->status(),
            'activeTab' => in_array($tab, $allowedTabs, true) ? $tab : 'summary',
        ]);
    }

    public function quickScan(Request $request, MaintenanceSnapshotRepository $snapshots): RedirectResponse
    {
        return $this->dispatchScan($request, $snapshots, 'quick');
    }

    public function deepScan(Request $request, MaintenanceSnapshotRepository $snapshots): RedirectResponse
    {
        return $this->dispatchScan($request, $snapshots, 'deep');
    }

    private function dispatchScan(
        Request $request,
        MaintenanceSnapshotRepository $snapshots,
        string $mode
    ): RedirectResponse {
        $status = $snapshots->status();
        if (in_array($status['status'] ?? null, ['queued', 'running'], true)) {
            return back()->with('error', 'Pemeriksaan lain masih berjalan.');
        }

        $snapshots->putStatus('queued', $mode, [
            'queued_at' => now()->toIso8601String(),
            'triggered_by' => $request->user()->getKey(),
        ]);
        RunMaintenanceScan::dispatch($mode, $request->user()->getKey());

        $message = $mode === 'quick'
            ? 'Pemeriksaan cepat telah dimasukkan ke antrean. Muat ulang halaman nanti untuk melihat hasilnya.'
            : 'Pemeriksaan mendalam telah dimasukkan ke antrean. Proses dapat memerlukan beberapa waktu.';

        return back()->with('success', $message);
    }
}
