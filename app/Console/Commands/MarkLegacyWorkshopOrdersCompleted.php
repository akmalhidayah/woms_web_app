<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Services\BengkelTasks\WorkshopHandoverQueue;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

final class MarkLegacyWorkshopOrdersCompleted extends Command
{
    protected $signature = 'workshop:mark-legacy-completed
        {--before= : Batas tanggal order (YYYY-MM-DD), bersifat wajib}
        {--completed-at= : Waktu penyelesaian legacy; default waktu command dijalankan}
        {--order=* : Batasi ke nomor order tertentu; dapat digunakan berulang}
        {--apply : Simpan perubahan; tanpa opsi ini command hanya melakukan dry-run}';

    protected $description = 'Tandai order bengkel lama yang sudah siap Serah Terima sebagai selesai tanpa membuat dokumen Serah Terima palsu.';

    public function handle(WorkshopHandoverQueue $queue): int
    {
        $before = $this->parseDateOption('before', true);
        $completedAtInput = trim((string) $this->option('completed-at'));
        $completedAt = $completedAtInput === ''
            ? now()
            : $this->parseDateOption('completed-at', false);

        if ($before === null || $completedAt === null) {
            return self::FAILURE;
        }

        $orderNumbers = collect((array) $this->option('order'))
            ->map(fn (mixed $number): string => trim((string) $number))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $query = $this->candidateQuery($queue, $before, $orderNumbers);
        $total = (clone $query)->count();

        $this->table(
            ['ID', 'Nomor Order', 'Tanggal Order', 'Progress'],
            (clone $query)
                ->limit(20)
                ->get(['orders.id', 'orders.nomor_order', 'orders.tanggal_order'])
                ->map(fn (Order $order): array => [
                    $order->id,
                    $order->nomor_order,
                    $order->tanggal_order?->format('Y-m-d') ?? '-',
                    $order->orderWorkshop?->progress_status ?? '-',
                ])
                ->all(),
        );

        if ($total > 20) {
            $this->line(sprintf('Menampilkan 20 dari %d kandidat.', $total));
        }

        if (! (bool) $this->option('apply')) {
            $this->info(sprintf('DRY-RUN selesai. %d order memenuhi kriteria; tidak ada data yang diubah.', $total));

            return self::SUCCESS;
        }

        $updated = 0;
        $query
            ->select('orders.*')
            ->chunkById(100, function ($orders) use ($queue, $before, $completedAt, $orderNumbers, &$updated): void {
                $orderIds = $orders->pluck('id')->map(fn ($id): int => (int) $id)->all();

                $updated += DB::transaction(function () use ($queue, $before, $completedAt, $orderNumbers, $orderIds): int {
                    $workshops = OrderWorkshop::query()
                        ->whereIn('order_id', $orderIds)
                        ->whereNull('legacy_completed_at')
                        ->lockForUpdate()
                        ->get();
                    $eligibleOrderIds = $this->candidateQuery($queue, $before, $orderNumbers)
                        ->whereKey($orderIds)
                        ->pluck('orders.id')
                        ->map(fn ($id): int => (int) $id)
                        ->all();
                    $changed = 0;

                    foreach ($workshops->whereIn('order_id', $eligibleOrderIds) as $workshop) {
                        $workshop->forceFill(['legacy_completed_at' => $completedAt])->save();
                        $changed++;
                    }

                    return $changed;
                });
            }, 'orders.id', 'id');

        $this->info(sprintf('%d order legacy berhasil ditandai selesai.', $updated));

        return self::SUCCESS;
    }

    /** @param list<string> $orderNumbers */
    private function candidateQuery(
        WorkshopHandoverQueue $queue,
        Carbon $before,
        array $orderNumbers,
    ): Builder {
        return $queue->query()
            ->whereDoesntHave('workshopHandover')
            ->whereDate('orders.tanggal_order', '<=', $before->toDateString())
            ->when($orderNumbers !== [], fn (Builder $query): Builder => $query
                ->whereIn('orders.nomor_order', $orderNumbers))
            ->orderBy('orders.id');
    }

    private function parseDateOption(string $name, bool $required): ?Carbon
    {
        $value = trim((string) $this->option($name));

        if ($value === '') {
            if ($required) {
                $this->error("Opsi --{$name}=YYYY-MM-DD wajib diisi.");
            }

            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            $this->error("Nilai --{$name} tidak valid: {$value}");

            return null;
        }
    }
}
