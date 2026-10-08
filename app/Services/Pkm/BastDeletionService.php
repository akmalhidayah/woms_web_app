<?php

namespace App\Services\Pkm;

use App\Models\Garansi;
use App\Models\LhppBast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BastDeletionService
{
    public function delete(LhppBast|int $bast): void
    {
        $paths = DB::transaction(function () use ($bast): array {
            $root = LhppBast::query()->whereKey($bast instanceof LhppBast ? $bast->getKey() : $bast)
                ->lockForUpdate()->firstOrFail();

            $documents = collect([$root]);
            if ($root->termin_type === 'termin_1') {
                $documents = $documents->merge(
                    LhppBast::query()->where('parent_lhpp_bast_id', $root->id)->lockForUpdate()->get()
                );
            }

            $documents->each(fn (LhppBast $document) => $document->loadMissing(['images', 'signatures', 'lpjPpl']));
            $paths = $documents->flatMap(function (LhppBast $document): array {
                $lpj = $document->lpjPpl;

                return array_merge(
                    $document->images->pluck('file_path')->all(),
                    [$document->attachment_pdf_path],
                    $document->signatures->flatMap(fn ($signature): array => [
                        $signature->signature_data,
                        $signature->signed_document_path,
                    ])->all(),
                    $lpj ? [
                        $lpj->lpj_document_path_termin1,
                        $lpj->ppl_document_path_termin1,
                        $lpj->lpj_document_path_termin2,
                        $lpj->ppl_document_path_termin2,
                    ] : [],
                );
            })->filter(fn (mixed $path): bool => $this->isPublicDiskPath($path))->unique()->values()->all();

            if ($root->termin_type === 'termin_1') {
                Garansi::query()->whereIn('lhpp_bast_id', $documents->pluck('id'))->update(['lhpp_bast_id' => null]);
                $documents->where('id', '!=', $root->id)->sortByDesc('id')->each->delete();
            }

            $root->delete();

            return $paths;
        });

        foreach ($paths as $path) {
            try {
                if (! Storage::disk('public')->delete($path)) {
                    Log::warning('BAST file could not be deleted.', ['path' => $path]);
                }
            } catch (Throwable $exception) {
                Log::warning('BAST file cleanup failed after database deletion.', [
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function isPublicDiskPath(mixed $path): bool
    {
        if (! is_string($path)) {
            return false;
        }

        $path = trim($path);

        return $path !== ''
            && ! str_starts_with($path, 'data:')
            && ! str_starts_with($path, '/')
            && ! str_contains($path, '://')
            && ! str_contains($path, '..');
    }
}
