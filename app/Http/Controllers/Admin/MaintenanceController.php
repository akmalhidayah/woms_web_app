<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Maintenance\LaravelLogReader;
use App\Services\Maintenance\MaintenanceScanService;
use App\Services\Maintenance\MaintenanceSnapshotRepository;
use App\Services\Maintenance\StagedDeepScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class MaintenanceController extends Controller
{
    public function index(
        Request $request,
        MaintenanceSnapshotRepository $snapshots,
        LaravelLogReader $logs
    ): View {
        $tab = $request->string('tab')->toString();
        $allowedTabs = ['summary', 'approval', 'documents', 'files', 'users_structure', 'queue_scheduler', 'logs'];
        $activeTab = in_array($tab, $allowedTabs, true) ? $tab : 'summary';

        return view('admin.maintenance.index', [
            'quickSnapshot' => $snapshots->snapshot('quick'),
            'deepSnapshot' => $snapshots->snapshot('deep'),
            'scanStatus' => $snapshots->status(),
            'latestLogs' => $activeTab === 'logs' ? $logs->latest(10) : [],
            'activeTab' => $activeTab,
        ]);
    }

    public function quickScan(
        Request $request,
        MaintenanceSnapshotRepository $snapshots,
        MaintenanceScanService $scanner
    ): RedirectResponse {
        $lock = Cache::lock(
            MaintenanceSnapshotRepository::LOCK_KEY,
            (int) config('maintenance.quick_scan_lock_seconds')
        );

        if (! $lock->get()) {
            return back()->with('error', 'Pemeriksaan lain sedang berjalan. Tunggu hingga pemeriksaan tersebut selesai.');
        }

        $startedAt = now();
        $started = hrtime(true);

        try {
            $snapshots->putStatus('running', 'quick', [
                'started_at' => $startedAt->toIso8601String(),
                'triggered_by' => $request->user()->getKey(),
            ]);
            $snapshot = $scanner->scan('quick');
            $snapshots->storeSnapshot('quick', $snapshot);
            $snapshots->putStatus('completed', 'quick', [
                'completed_at' => $snapshot['completed_at'],
                'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                'triggered_by' => $request->user()->getKey(),
            ]);

            return redirect()
                ->route('admin.maintenance.index')
                ->with('success', 'Pemeriksaan cepat berhasil diselesaikan.');
        } catch (Throwable $exception) {
            report($exception);
            $snapshots->putStatus('failed', 'quick', [
                'failed_at' => now()->toIso8601String(),
                'triggered_by' => $request->user()->getKey(),
                'message' => 'Pemeriksaan cepat gagal dijalankan.',
            ]);

            return back()->with('error', 'Pemeriksaan cepat gagal dijalankan. Silakan periksa log aplikasi.');
        } finally {
            $lock->release();
        }
    }

    public function startDeepScan(Request $request, StagedDeepScanService $scanner): JsonResponse
    {
        try {
            return response()->json($scanner->start((int) $request->user()->getKey()));
        } catch (HttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Pemeriksaan mendalam tidak dapat dimulai. Silakan periksa log aplikasi.',
            ], 500);
        }
    }

    public function stepDeepScan(Request $request, StagedDeepScanService $scanner): JsonResponse
    {
        $validated = $request->validate([
            'scan_id' => ['required', 'string', 'size:48'],
            'step' => ['required', 'string', 'in:system,approval,documents,files,users_structure,queue_scheduler'],
        ]);

        try {
            return response()->json($scanner->step(
                $validated['scan_id'],
                $validated['step'],
                (int) $request->user()->getKey()
            ));
        } catch (HttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            $scanner->fail($validated['scan_id'], (int) $request->user()->getKey());

            return response()->json([
                'success' => false,
                'message' => 'Pemeriksaan mendalam tidak dapat diselesaikan. Silakan periksa log aplikasi.',
            ], 500);
        }
    }

    public function finalizeDeepScan(Request $request, StagedDeepScanService $scanner): JsonResponse
    {
        $validated = $request->validate([
            'scan_id' => ['required', 'string', 'size:48'],
        ]);

        try {
            return response()->json([
                ...$scanner->finalize($validated['scan_id'], (int) $request->user()->getKey()),
                'redirect_url' => route('admin.maintenance.index', ['tab' => 'files']),
            ]);
        } catch (HttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Pemeriksaan mendalam tidak dapat diselesaikan. Silakan periksa log aplikasi.',
            ], 500);
        }
    }

    public function cancelDeepScan(Request $request, StagedDeepScanService $scanner): JsonResponse
    {
        $validated = $request->validate([
            'scan_id' => ['required', 'string', 'size:48'],
        ]);

        try {
            $scanner->cancel($validated['scan_id'], (int) $request->user()->getKey());
        } catch (HttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Pemeriksaan tidak dapat dibatalkan. Silakan periksa log aplikasi.',
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Pemeriksaan mendalam dibatalkan.']);
    }
}
