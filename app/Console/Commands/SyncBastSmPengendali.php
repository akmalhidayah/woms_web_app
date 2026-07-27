<?php

namespace App\Console\Commands;

use App\Models\LhppBast;
use App\Services\Approvals\BastSmPengendaliSynchronizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncBastSmPengendali extends Command
{
    protected $signature = 'bast:sync-sm-pengendali
        {--dry-run : Periksa perubahan tanpa menyimpan data}
        {--bast-id= : Batasi sinkronisasi pada satu ID BAST}';

    protected $description = 'Menyisipkan SM Pengendali ke flow dan signature BAST lama secara idempotent.';

    public function handle(BastSmPengendaliSynchronizer $synchronizer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $bastId = $this->option('bast-id');
        $summary = [
            'total' => 0,
            'flow_updated' => 0,
            'sm_added' => 0,
            'gm_pending_redirected' => 0,
            'unchanged' => 0,
            'skipped_rejected' => 0,
            'failed' => 0,
        ];

        LhppBast::query()
            ->when(filled($bastId), fn ($query) => $query->whereKey((int) $bastId))
            ->orderBy('id')
            ->chunkById(100, function ($basts) use ($synchronizer, $dryRun, &$summary): void {
                foreach ($basts as $bast) {
                    $summary['total']++;

                    try {
                        if ($dryRun) {
                            DB::beginTransaction();
                        }

                        $result = $synchronizer->sync($bast);

                        if ($dryRun) {
                            DB::rollBack();
                        }

                        $summary['flow_updated'] += (int) $result['flow_updated'];
                        $summary['sm_added'] += (int) $result['sm_added'];
                        $summary['gm_pending_redirected'] += (int) $result['gm_pending_redirected'];
                        $summary[$result['status']] = ($summary[$result['status']] ?? 0) + 1;
                    } catch (Throwable $exception) {
                        if ($dryRun && DB::transactionLevel() > 0) {
                            DB::rollBack();
                        }

                        $summary['failed']++;
                        $this->error("BAST {$bast->id}: {$exception->getMessage()}");
                    }
                }
            });

        $this->table(['Keterangan', 'Jumlah'], [
            ['Total diperiksa', $summary['total']],
            ['Flow diperbarui', $summary['flow_updated']],
            ['SM ditambahkan', $summary['sm_added']],
            ['GM pending dialihkan', $summary['gm_pending_redirected']],
            ['Sudah sesuai', $summary['unchanged']],
            ['Rejected dilewati', $summary['skipped_rejected']],
            ['Gagal', $summary['failed']],
        ]);
        $this->info($dryRun ? 'DRY-RUN selesai. Tidak ada data yang diubah.' : 'Sinkronisasi selesai.');

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
