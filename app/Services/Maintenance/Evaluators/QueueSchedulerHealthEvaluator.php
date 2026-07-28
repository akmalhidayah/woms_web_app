<?php

namespace App\Services\Maintenance\Evaluators;

use App\Services\Maintenance\MaintenanceSnapshotRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueSchedulerHealthEvaluator implements MaintenanceEvaluator
{
    public function __construct(private readonly MaintenanceSnapshotRepository $snapshots) {}

    public function category(): string
    {
        return 'queue_scheduler';
    }

    public function evaluate(string $mode): array
    {
        $findings = [];

        if (Schema::hasTable('failed_jobs')) {
            $failedCount = DB::table('failed_jobs')->count();
            if ($failedCount > 0) {
                $latest = DB::table('failed_jobs')
                    ->select(['id', 'connection', 'queue', 'failed_at', 'exception'])
                    ->latest('failed_at')
                    ->first();
                $findings[] = $this->finding(
                    'failed_queue_jobs',
                    'warning',
                    'Terdapat '.$failedCount.' failed job.',
                    [
                        'failed_jobs' => $failedCount,
                        'latest_id' => $latest?->id,
                        'connection' => $latest?->connection,
                        'queue' => $latest?->queue,
                        'failed_at' => $latest?->failed_at,
                        'exception' => $this->sanitizeException((string) ($latest?->exception ?? '')),
                    ]
                );
            }
        }

        $heartbeat = $this->snapshots->heartbeat();
        if (! $heartbeat) {
            $findings[] = $this->finding('scheduler_heartbeat_missing', 'info', 'Status scheduler belum tersedia.');
        } elseif (Carbon::parse($heartbeat)->lt(now()->subMinutes((int) config('maintenance.scheduler_heartbeat_ttl_minutes')))) {
            $findings[] = $this->finding('scheduler_heartbeat_stale', 'warning', 'Scheduler tidak terdeteksi berjalan dalam 15 menit terakhir.');
        }

        return $findings;
    }

    private function sanitizeException(string $exception): string
    {
        $firstLine = trim(strtok($exception, "\n") ?: '');

        return mb_substr(preg_replace('/(token|password|secret|key)\\s*[=:]\\s*\\S+/i', '$1=[REDACTED]', $firstLine) ?? '', 0, 240);
    }

    private function finding(string $code, string $severity, string $description, array $meta = []): array
    {
        return [
            'code' => $code,
            'severity' => $severity,
            'module' => 'Queue & Scheduler',
            'title' => $description,
            'description' => $description,
            'subject_type' => 'queue_scheduler',
            'subject_id' => $meta['latest_id'] ?? null,
            'reference' => isset($meta['latest_id']) ? (string) $meta['latest_id'] : null,
            'url' => null,
            'secondary_url' => null,
            'meta' => $meta,
            'detected_at' => now()->toIso8601String(),
        ];
    }
}
