<?php

namespace App\Services\Maintenance\Evaluators;

use App\Models\AdminInformationFile;
use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBastSignature;
use App\Models\OrderDocument;
use App\Models\PurchaseOrder;
use App\Models\QualityControlReportFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FileStorageHealthEvaluator implements MaintenanceEvaluator
{
    public function category(): string
    {
        return 'files';
    }

    public function evaluate(string $mode): array
    {
        if ($mode !== 'deep') {
            return [];
        }

        $findings = [];
        foreach ($this->sources() as [$model, $column, $module]) {
            $model::query()
                ->select(['id', $column])
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->chunkById((int) config('maintenance.chunk_size'), function ($rows) use (&$findings, $column, $module): void {
                    foreach ($rows as $row) {
                        if (count($findings) >= (int) config('maintenance.max_findings_per_category')) {
                            return;
                        }

                        $path = trim((string) $row->{$column});
                        if ($this->isInlineData($path) || $this->exists($path)) {
                            continue;
                        }

                        $findings[] = $this->finding($module, $row, $path);
                    }
                });
        }

        return $findings;
    }

    /**
     * @return array{findings: list<array<string, mixed>>, next_source: int, next_id: int, finished: bool}
     */
    public function evaluateChunk(int $sourceIndex, int $lastId = 0): array
    {
        $sources = $this->sources();
        if (! isset($sources[$sourceIndex])) {
            return [
                'findings' => [],
                'next_source' => $sourceIndex,
                'next_id' => $lastId,
                'finished' => true,
            ];
        }

        [$model, $column, $module] = $sources[$sourceIndex];
        $rows = $model::query()
            ->select(['id', $column])
            ->where('id', '>', $lastId)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy('id')
            ->limit((int) config('maintenance.deep_scan_chunk_size'))
            ->get();

        $findings = [];
        foreach ($rows as $row) {
            $path = trim((string) $row->{$column});
            if ($this->isInlineData($path) || $this->exists($path)) {
                continue;
            }

            $findings[] = $this->finding($module, $row, $path);
        }

        $chunkSize = (int) config('maintenance.deep_scan_chunk_size');
        $sourceFinished = $rows->count() < $chunkSize;
        $nextSource = $sourceFinished ? $sourceIndex + 1 : $sourceIndex;

        return [
            'findings' => $findings,
            'next_source' => $nextSource,
            'next_id' => $sourceFinished ? 0 : (int) $rows->last()->getKey(),
            'finished' => $nextSource >= count($sources),
        ];
    }

    public function sourceCount(): int
    {
        return count($this->sources());
    }

    /**
     * @return list<array{class-string<Model>, string, string}>
     */
    private function sources(): array
    {
        return [
            [OrderDocument::class, 'path_file', 'Order'],
            [QualityControlReportFile::class, 'file_path', 'Quality Control'],
            [PurchaseOrder::class, 'po_document_path', 'Purchase Order'],
            [AdminInformationFile::class, 'file_path', 'Informasi'],
            [HppSignature::class, 'signed_document_path', 'HPP'],
            [LhppBastSignature::class, 'signed_document_path', 'BAST'],
            [InitialWorkSignature::class, 'signature_path', 'Initial Work'],
        ];
    }

    private function isInlineData(string $path): bool
    {
        return str_starts_with($path, 'data:');
    }

    private function exists(string $path): bool
    {
        if (str_starts_with($path, storage_path())) {
            return is_file($path);
        }

        $relative = ltrim(preg_replace('#^(storage/|public/)#', '', $path) ?? $path, '/');

        return Storage::disk('public')->exists($relative)
            || Storage::disk('local')->exists($relative);
    }

    private function finding(string $module, Model $model, string $path): array
    {
        return [
            'code' => 'recorded_file_missing',
            'severity' => 'warning',
            'module' => $module,
            'title' => 'File tercatat tetapi tidak ditemukan.',
            'description' => 'File tercatat di database tetapi tidak ditemukan di storage.',
            'subject_type' => strtolower(str_replace(' ', '_', $module)),
            'subject_id' => $model->getKey(),
            'reference' => (string) $model->getKey(),
            'url' => null,
            'secondary_url' => null,
            'meta' => ['path' => basename($path)],
            'detected_at' => now()->toIso8601String(),
        ];
    }
}
