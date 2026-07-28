<?php

namespace App\Console\Commands;

use App\Jobs\Maintenance\RunMaintenanceScan;
use App\Services\Maintenance\MaintenanceSnapshotRepository;
use Illuminate\Console\Command;

class RunMaintenanceScanCommand extends Command
{
    protected $signature = 'maintenance:scan {mode : quick atau deep} {--sync : Jalankan pada proses saat ini}';

    protected $description = 'Masukkan pemeriksaan Maintenance Sistem ke antrean';

    public function handle(MaintenanceSnapshotRepository $snapshots): int
    {
        $mode = (string) $this->argument('mode');
        if (! in_array($mode, ['quick', 'deep'], true)) {
            $this->error('Mode harus quick atau deep.');

            return self::INVALID;
        }

        $snapshots->putStatus('queued', $mode, ['queued_at' => now()->toIso8601String()]);

        if ($this->option('sync')) {
            RunMaintenanceScan::dispatchSync($mode);
        } else {
            RunMaintenanceScan::dispatch($mode);
        }

        $this->info("Pemeriksaan {$mode} telah dimasukkan ke antrean.");

        return self::SUCCESS;
    }
}
