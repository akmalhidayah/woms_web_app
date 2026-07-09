<?php

namespace App\Console\Commands;

use App\Models\Hpp;
use App\Support\HppDocumentNumberGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BackfillHppDocumentNumbers extends Command
{
    protected $signature = 'hpp:backfill-document-numbers
        {--dry-run : Show HPP rows and generated numbers without updating data}';

    protected $description = 'Backfill HPP document numbers for submitted HPP records that do not have one yet.';

    public function handle(HppDocumentNumberGenerator $generator): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $candidates = $this->candidateQuery()->get();

        if ($candidates->isEmpty()) {
            $this->info('Tidak ada HPP submitted/final tanpa nomor dokumen.');

            return self::SUCCESS;
        }

        $rows = $dryRun
            ? $this->previewRows($candidates, $generator)
            : $this->applyRows($candidates, $generator);

        $this->table([
            'HPP ID',
            'Order No',
            'Status',
            'Tanggal Nomor',
            'Document No',
        ], $rows);

        $this->info($dryRun
            ? 'DRY-RUN selesai. Tidak ada data yang diubah.'
            : sprintf('Backfill selesai. %d HPP diperbarui.', count($rows)));

        return self::SUCCESS;
    }

    private function candidateQuery()
    {
        return Hpp::query()
            ->whereNull('document_no')
            ->where('status', '!=', Hpp::STATUS_DRAFT)
            ->orderByRaw('COALESCE(submitted_at, created_at) asc')
            ->orderBy('id');
    }

    /**
     * @param \Illuminate\Support\Collection<int, Hpp> $candidates
     * @return list<array<int, mixed>>
     */
    private function previewRows($candidates, HppDocumentNumberGenerator $generator): array
    {
        $lastSequenceByYear = [];
        $rows = [];

        foreach ($candidates as $hpp) {
            $date = $this->documentDate($hpp);
            $year = (int) $date->format('Y');

            if (! array_key_exists($year, $lastSequenceByYear)) {
                $lastSequenceByYear[$year] = $generator->nextSequenceForYear($year) - 1;
            }

            $lastSequenceByYear[$year]++;
            $payload = $generator->format($date, $lastSequenceByYear[$year]);
            $rows[] = $this->row($hpp, $date, $payload['document_no']);
        }

        return $rows;
    }

    /**
     * @param \Illuminate\Support\Collection<int, Hpp> $candidates
     * @return list<array<int, mixed>>
     */
    private function applyRows($candidates, HppDocumentNumberGenerator $generator): array
    {
        return DB::transaction(function () use ($candidates, $generator): array {
            $rows = [];

            foreach ($candidates as $candidate) {
                $hpp = Hpp::query()
                    ->whereKey($candidate->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $hpp || filled($hpp->document_no) || $hpp->status === Hpp::STATUS_DRAFT) {
                    continue;
                }

                $date = $this->documentDate($hpp);
                $payload = $generator->assignTo($hpp, $date);
                $hpp->save();

                $rows[] = $this->row($hpp, $date, $payload['document_no']);
            }

            return $rows;
        });
    }

    private function documentDate(Hpp $hpp): Carbon
    {
        return Carbon::parse($hpp->submitted_at ?: $hpp->created_at ?: now());
    }

    /**
     * @return array<int, mixed>
     */
    private function row(Hpp $hpp, Carbon $date, string $documentNo): array
    {
        return [
            $hpp->id,
            $hpp->nomor_order,
            $hpp->status,
            $date->format('d/m/Y'),
            $documentNo,
        ];
    }
}
