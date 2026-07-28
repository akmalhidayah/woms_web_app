<?php

namespace App\Services\Maintenance;

use App\Services\Maintenance\Evaluators\FileStorageHealthEvaluator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class StagedDeepScanService
{
    /**
     * @var list<string>
     */
    private const STEPS = [
        'system',
        'approval',
        'documents',
        'files',
        'users_structure',
        'queue_scheduler',
    ];

    public function __construct(
        private readonly MaintenanceScanService $scanner,
        private readonly MaintenanceSnapshotRepository $snapshots,
        private readonly FileStorageHealthEvaluator $files,
    ) {}

    public function start(int $userId): array
    {
        $lock = Cache::lock(
            MaintenanceSnapshotRepository::LOCK_KEY,
            (int) config('maintenance.deep_scan_lock_seconds')
        );

        if (! $lock->get()) {
            throw new HttpException(409, 'Pemeriksaan lain sedang berjalan. Tunggu hingga pemeriksaan tersebut selesai.');
        }

        $scanId = Str::random(48);
        $startedAt = now()->toIso8601String();
        $context = [
            'scan_id' => $scanId,
            'mode' => 'deep',
            'status' => 'running',
            'triggered_by' => $userId,
            'started_at' => $startedAt,
            'current_step' => self::STEPS[0],
            'progress' => 0,
            'cursors' => ['files' => ['source' => 0, 'last_id' => 0]],
            'lock_owner' => $lock->owner(),
        ];

        try {
            $this->snapshots->putDeepContext($scanId, $context);
            $this->snapshots->putDeepFindings($scanId, $this->emptyCategories());
            $this->snapshots->putStatus('running', 'deep', [
                'scan_id' => $scanId,
                'started_at' => $startedAt,
                'triggered_by' => $userId,
                'progress' => 0,
                'current_step' => self::STEPS[0],
            ]);
        } catch (Throwable $exception) {
            $lock->release();
            throw $exception;
        }

        return $this->publicContext($context);
    }

    public function step(string $scanId, string $requestedStep, int $userId): array
    {
        $context = $this->ownedContext($scanId, $userId);
        if ($context['status'] !== 'running') {
            throw new HttpException(409, 'Pemeriksaan mendalam tidak lagi berjalan.');
        }
        if (! hash_equals((string) $context['current_step'], $requestedStep)) {
            throw new HttpException(422, 'Tahap pemeriksaan tidak sesuai urutan.');
        }

        $categories = $this->snapshots->deepFindings($scanId);
        $stepFinished = true;

        if ($requestedStep === 'files') {
            $cursor = $context['cursors']['files'] ?? ['source' => 0, 'last_id' => 0];
            $result = $this->files->evaluateChunk((int) $cursor['source'], (int) $cursor['last_id']);
            $categories['files'] = $this->mergeFindings(
                $categories['files'] ?? [],
                $result['findings']
            );
            $context['cursors']['files'] = [
                'source' => $result['next_source'],
                'last_id' => $result['next_id'],
            ];
            $stepFinished = $result['finished'];
        } else {
            $categories[$requestedStep] = $this->scanner->scanCategory('deep', $requestedStep);
        }

        if ($stepFinished) {
            $currentIndex = array_search($requestedStep, self::STEPS, true);
            $nextStep = self::STEPS[$currentIndex + 1] ?? null;
            $context['current_step'] = $nextStep ?? 'finalize';
        }

        $context['progress'] = $this->progress($context);
        $context['updated_at'] = now()->toIso8601String();
        $this->snapshots->putDeepFindings($scanId, $categories);
        $this->snapshots->putDeepContext($scanId, $context);
        $this->snapshots->putStatus('running', 'deep', [
            'scan_id' => $scanId,
            'started_at' => $context['started_at'],
            'triggered_by' => $userId,
            'progress' => $context['progress'],
            'current_step' => $context['current_step'],
        ]);

        return [
            'success' => true,
            'finished' => $context['current_step'] === 'finalize',
            'current_step' => $requestedStep,
            'next_step' => $context['current_step'],
            'progress' => $context['progress'],
            'finding_count' => collect($categories)->flatten(1)->count(),
            'message' => $this->stepMessage($context['current_step']),
        ];
    }

    public function finalize(string $scanId, int $userId): array
    {
        $context = $this->ownedContext($scanId, $userId);
        if ($context['current_step'] !== 'finalize') {
            throw new HttpException(422, 'Pemeriksaan mendalam belum menyelesaikan seluruh tahap.');
        }

        try {
            $startedAt = Carbon::parse($context['started_at']);
            $snapshot = $this->scanner->buildSnapshot(
                'deep',
                $this->snapshots->deepFindings($scanId),
                $startedAt
            );
            $this->snapshots->storeSnapshot('deep', $snapshot);
            $this->snapshots->putStatus('completed', 'deep', [
                'scan_id' => $scanId,
                'completed_at' => $snapshot['completed_at'],
                'duration_ms' => $snapshot['duration_ms'],
                'progress' => 100,
            ]);

            return ['success' => true, 'progress' => 100];
        } catch (Throwable $exception) {
            report($exception);
            $this->snapshots->putStatus('failed', 'deep', [
                'scan_id' => $scanId,
                'failed_at' => now()->toIso8601String(),
                'triggered_by' => $userId,
                'message' => 'Pemeriksaan mendalam tidak dapat diselesaikan. Silakan periksa log aplikasi.',
            ]);

            throw $exception;
        } finally {
            $this->release($context);
            $this->snapshots->forgetDeepScan($scanId);
        }
    }

    public function cancel(string $scanId, int $userId): void
    {
        $context = $this->ownedContext($scanId, $userId);
        $this->snapshots->putStatus('cancelled', 'deep', [
            'scan_id' => $scanId,
            'cancelled_at' => now()->toIso8601String(),
            'triggered_by' => $userId,
        ]);
        $this->release($context);
        $this->snapshots->forgetDeepScan($scanId);
    }

    public function fail(string $scanId, int $userId): void
    {
        $context = $this->snapshots->deepContext($scanId);
        if (! $context || (int) ($context['triggered_by'] ?? 0) !== $userId) {
            return;
        }

        $this->snapshots->putStatus('failed', 'deep', [
            'scan_id' => $scanId,
            'failed_at' => now()->toIso8601String(),
            'triggered_by' => $userId,
            'message' => 'Pemeriksaan mendalam tidak dapat diselesaikan. Silakan periksa log aplikasi.',
        ]);
        $this->release($context);
        $this->snapshots->forgetDeepScan($scanId);
    }

    private function ownedContext(string $scanId, int $userId): array
    {
        $context = $this->snapshots->deepContext($scanId);
        if (! $context) {
            throw new HttpException(410, 'Sesi pemeriksaan sudah berakhir atau tidak ditemukan.');
        }
        if ((int) ($context['triggered_by'] ?? 0) !== $userId) {
            throw new HttpException(403, 'Anda tidak dapat melanjutkan pemeriksaan milik pengguna lain.');
        }

        return $context;
    }

    private function release(array $context): void
    {
        $owner = (string) ($context['lock_owner'] ?? '');
        if ($owner !== '') {
            Cache::restoreLock(MaintenanceSnapshotRepository::LOCK_KEY, $owner)->release();
        }
    }

    private function progress(array $context): int
    {
        if ($context['current_step'] === 'finalize') {
            return 95;
        }

        $index = array_search($context['current_step'], self::STEPS, true);
        if ($context['current_step'] === 'files') {
            $source = (int) ($context['cursors']['files']['source'] ?? 0);
            $fraction = $source / max(1, $this->files->sourceCount());

            return (int) floor((($index + $fraction) / count(self::STEPS)) * 95);
        }

        return (int) floor(($index / count(self::STEPS)) * 95);
    }

    private function mergeFindings(array $current, array $additional): array
    {
        return array_slice(
            [...$current, ...$additional],
            0,
            (int) config('maintenance.max_findings_per_category')
        );
    }

    private function emptyCategories(): array
    {
        return array_fill_keys(self::STEPS, []);
    }

    private function publicContext(array $context): array
    {
        return [
            'success' => true,
            'scan_id' => $context['scan_id'],
            'current_step' => $context['current_step'],
            'progress' => $context['progress'],
            'message' => $this->stepMessage($context['current_step']),
        ];
    }

    private function stepMessage(string $step): string
    {
        return match ($step) {
            'system' => 'Memeriksa database, cache, dan storage...',
            'approval' => 'Memeriksa konsistensi approval...',
            'documents' => 'Memeriksa konsistensi dokumen...',
            'files' => 'Memeriksa file dan storage...',
            'users_structure' => 'Memeriksa user dan struktur organisasi...',
            'queue_scheduler' => 'Memeriksa queue dan scheduler...',
            default => 'Menyiapkan hasil pemeriksaan...',
        };
    }
}
