<?php

namespace App\Services\Maintenance;

use App\Services\Maintenance\Evaluators\ApprovalHealthEvaluator;
use App\Services\Maintenance\Evaluators\DocumentHealthEvaluator;
use App\Services\Maintenance\Evaluators\FileStorageHealthEvaluator;
use App\Services\Maintenance\Evaluators\MaintenanceEvaluator;
use App\Services\Maintenance\Evaluators\QueueSchedulerHealthEvaluator;
use App\Services\Maintenance\Evaluators\SystemHealthEvaluator;
use App\Services\Maintenance\Evaluators\UserStructureHealthEvaluator;
use Illuminate\Support\Facades\Log;
use Throwable;

class MaintenanceScanService
{
    /**
     * @param  list<MaintenanceEvaluator>  $evaluators
     */
    public function __construct(
        private readonly SystemHealthEvaluator $system,
        private readonly ApprovalHealthEvaluator $approval,
        private readonly DocumentHealthEvaluator $documents,
        private readonly FileStorageHealthEvaluator $files,
        private readonly UserStructureHealthEvaluator $usersStructure,
        private readonly QueueSchedulerHealthEvaluator $queueScheduler,
    ) {}

    public function scan(string $mode): array
    {
        if (! in_array($mode, ['quick', 'deep'], true)) {
            throw new \InvalidArgumentException('Mode maintenance tidak valid.');
        }

        $started = now();
        $categories = array_fill_keys([
            'system', 'approval', 'documents', 'files', 'users_structure', 'queue_scheduler',
        ], []);

        foreach ($this->evaluators() as $evaluator) {
            $timer = hrtime(true);
            try {
                $categories[$evaluator->category()] = $evaluator->evaluate($mode);
            } catch (Throwable $exception) {
                Log::error('maintenance.scan.evaluator_failed', [
                    'mode' => $mode,
                    'evaluator' => $evaluator::class,
                    'exception' => $exception::class,
                    'message' => mb_substr($exception->getMessage(), 0, 240),
                ]);
                $categories[$evaluator->category()] = [$this->failedEvaluatorFinding($evaluator)];
            } finally {
                Log::info('maintenance.scan.evaluator_completed', [
                    'mode' => $mode,
                    'evaluator' => $evaluator::class,
                    'duration_ms' => (int) ((hrtime(true) - $timer) / 1_000_000),
                    'finding_count' => count($categories[$evaluator->category()] ?? []),
                ]);
            }
        }

        $all = collect($categories)->flatten(1);
        $completed = now();

        return [
            'status' => 'completed',
            'mode' => $mode,
            'started_at' => $started->toIso8601String(),
            'completed_at' => $completed->toIso8601String(),
            'duration_ms' => $started->diffInMilliseconds($completed),
            'summary' => [
                'critical' => $all->where('severity', 'critical')->count(),
                'warning' => $all->where('severity', 'warning')->count(),
                'info' => $all->where('severity', 'info')->count(),
                'total' => $all->count(),
            ],
            'categories' => $categories,
        ];
    }

    /**
     * @return list<MaintenanceEvaluator>
     */
    private function evaluators(): array
    {
        return [$this->system, $this->approval, $this->documents, $this->files, $this->usersStructure, $this->queueScheduler];
    }

    private function failedEvaluatorFinding(MaintenanceEvaluator $evaluator): array
    {
        return [
            'code' => 'evaluator_failed',
            'severity' => 'warning',
            'module' => class_basename($evaluator),
            'title' => 'Pemeriksaan modul tidak dapat diselesaikan.',
            'description' => 'Pemeriksaan modul tidak dapat diselesaikan. Periksa log aplikasi.',
            'subject_type' => 'system',
            'subject_id' => null,
            'reference' => null,
            'url' => null,
            'secondary_url' => null,
            'meta' => [],
            'detected_at' => now()->toIso8601String(),
        ];
    }
}
