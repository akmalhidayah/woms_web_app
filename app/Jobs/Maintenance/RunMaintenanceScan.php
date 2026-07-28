<?php

namespace App\Jobs\Maintenance;

use App\Services\Maintenance\MaintenanceScanService;
use App\Services\Maintenance\MaintenanceSnapshotRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunMaintenanceScan implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 60;

    public int $timeout;

    public function __construct(
        public readonly string $mode,
        public readonly ?int $triggeredBy = null,
    ) {
        if (! in_array($mode, ['quick', 'deep'], true)) {
            throw new \InvalidArgumentException('Mode maintenance tidak valid.');
        }

        $this->timeout = (int) config('maintenance.job_timeout_seconds');
    }

    public function handle(
        MaintenanceScanService $scanner,
        MaintenanceSnapshotRepository $snapshots
    ): void {
        $lock = Cache::lock(
            MaintenanceSnapshotRepository::LOCK_KEY,
            (int) config('maintenance.lock_seconds')
        );

        if (! $lock->get()) {
            $snapshots->putStatus('skipped', $this->mode, [
                'message' => 'Pemeriksaan lain masih berjalan.',
            ]);

            return;
        }

        try {
            $snapshots->putStatus('running', $this->mode, [
                'started_at' => now()->toIso8601String(),
                'triggered_by' => $this->triggeredBy,
            ]);
            $snapshot = $scanner->scan($this->mode);
            $snapshots->storeSnapshot($this->mode, $snapshot);
            $snapshots->putStatus('completed', $this->mode, [
                'completed_at' => $snapshot['completed_at'],
                'duration_ms' => $snapshot['duration_ms'],
            ]);
        } catch (Throwable $exception) {
            $snapshots->putStatus('failed', $this->mode, [
                'failed_at' => now()->toIso8601String(),
                'message' => 'Pemeriksaan gagal. Periksa log aplikasi.',
            ]);
            Log::error('maintenance.scan.failed', [
                'mode' => $this->mode,
                'triggered_by' => $this->triggeredBy,
                'exception' => $exception::class,
                'message' => mb_substr($exception->getMessage(), 0, 240),
            ]);

            throw $exception;
        } finally {
            $lock->release();
        }
    }
}
