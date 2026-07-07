<?php

namespace App\Console\Commands;

use App\Models\HppSignature;
use App\Support\SignatureImageStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReprocessHppSignatureImages extends Command
{
    protected $signature = 'hpp:crop-signatures
        {--signature-id= : Process one hpp_signatures.id}
        {--hpp-id= : Process all signature images for one hpps.id}
        {--apply : Write cropped files. Without this option the command runs in dry-run mode}
        {--dry-run : Preview only; kept for explicit safe runs}';

    protected $description = 'Crop whitespace from existing HPP signature images without changing approval data.';

    public function handle(): int
    {
        $signatureId = $this->option('signature-id');
        $hppId = $this->option('hpp-id');
        $apply = (bool) $this->option('apply');

        if ($signatureId && $hppId) {
            $this->error('Use either --signature-id or --hpp-id, not both.');

            return self::FAILURE;
        }

        if (! $signatureId && ! $hppId) {
            $this->error('Specify --signature-id=<id> or --hpp-id=<id>.');

            return self::FAILURE;
        }

        $query = HppSignature::query()
            ->whereNotNull('signature_data')
            ->whereRaw("TRIM(signature_data) <> ''")
            ->orderBy('id');

        if ($signatureId) {
            $query->whereKey((int) $signatureId);
        }

        if ($hppId) {
            $query->where('hpp_id', (int) $hppId);
        }

        $signatures = $query->get();

        if ($signatures->isEmpty()) {
            $this->warn('No HPP signature image records found for the selected target.');

            return self::SUCCESS;
        }

        $this->info($apply
            ? 'APPLY mode: files will be backed up before overwrite.'
            : 'DRY-RUN mode: no files will be changed.');
        $this->line('This command only crops source signature images. It does not change approval status, signer, dates, IP, user agent, or final signed documents.');

        $counts = [
            'checked' => 0,
            'would_crop' => 0,
            'cropped' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($signatures as $signature) {
            $counts['checked']++;
            $result = $this->processSignature($signature, $apply);
            $counts[$result]++;
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], collect($counts)->map(fn (int $count, string $metric): array => [
            $metric,
            $count,
        ])->values()->all());

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processSignature(HppSignature $signature, bool $apply): string
    {
        $path = trim((string) $signature->signature_data);

        if ($path === '' || str_starts_with($path, 'data:image')) {
            $this->warn("signature {$signature->id}: skipped unsupported signature_data value.");

            return 'skipped';
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            $this->warn("signature {$signature->id}: skipped missing file {$path}.");

            return 'skipped';
        }

        $original = $disk->get($path);

        if (! is_string($original) || $original === '') {
            $this->warn("signature {$signature->id}: skipped unreadable file {$path}.");

            return 'skipped';
        }

        $originalSize = $this->imageSize($original);
        $cropped = SignatureImageStorage::trimSignatureWhitespace($original);
        $croppedSize = $this->imageSize($cropped);

        if ($cropped === $original) {
            $this->line("signature {$signature->id}: no crop needed {$this->formatSize($originalSize)}.");

            return 'skipped';
        }

        if (! $apply) {
            $this->line("signature {$signature->id}: would crop {$this->formatSize($originalSize)} -> {$this->formatSize($croppedSize)} ({$path}).");

            return 'would_crop';
        }

        $backupPath = $this->backupPath($signature, $path);

        if (! $disk->put($backupPath, $original)) {
            $this->error("signature {$signature->id}: failed to write backup {$backupPath}.");

            return 'failed';
        }

        if (! $disk->put($path, $cropped)) {
            $this->error("signature {$signature->id}: failed to overwrite {$path}. Backup kept at {$backupPath}.");

            return 'failed';
        }

        $this->info("signature {$signature->id}: cropped {$this->formatSize($originalSize)} -> {$this->formatSize($croppedSize)}. Backup: {$backupPath}");

        return 'cropped';
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function imageSize(string $binary): ?array
    {
        $size = @getimagesizefromstring($binary);

        if (! is_array($size)) {
            return null;
        }

        return [(int) $size[0], (int) $size[1]];
    }

    /**
     * @param array{0: int, 1: int}|null $size
     */
    private function formatSize(?array $size): string
    {
        return $size ? "{$size[0]}x{$size[1]}" : 'unknown-size';
    }

    private function backupPath(HppSignature $signature, string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'png';
        $filename = sprintf(
            'signature-%d-%s.%s',
            $signature->id,
            Str::uuid(),
            $extension,
        );

        return 'signature-backups/hpp/'.now()->format('Ymd-His').'/'.$filename;
    }
}
