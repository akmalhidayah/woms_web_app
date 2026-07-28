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
        $models = [
            [OrderDocument::class, 'path_file', 'Order'],
            [QualityControlReportFile::class, 'file_path', 'Quality Control'],
            [PurchaseOrder::class, 'po_document_path', 'Purchase Order'],
            [AdminInformationFile::class, 'file_path', 'Informasi'],
            [HppSignature::class, 'signed_document_path', 'HPP'],
            [LhppBastSignature::class, 'signed_document_path', 'BAST'],
            [InitialWorkSignature::class, 'signature_path', 'Initial Work'],
        ];

        foreach ($models as [$model, $column, $module]) {
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
