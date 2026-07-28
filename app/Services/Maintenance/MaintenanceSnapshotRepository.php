<?php

namespace App\Services\Maintenance;

use Illuminate\Support\Facades\Cache;

class MaintenanceSnapshotRepository
{
    public const QUICK_SNAPSHOT_KEY = 'woms:maintenance:quick:latest';

    public const DEEP_SNAPSHOT_KEY = 'woms:maintenance:deep:latest';

    public const STATUS_KEY = 'woms:maintenance:scan:status';

    public const LOCK_KEY = 'woms:maintenance:scan:lock';

    public const SCHEDULER_HEARTBEAT_KEY = 'woms:maintenance:scheduler:heartbeat';

    public function snapshot(string $mode): ?array
    {
        $value = Cache::get($this->snapshotKey($mode));

        return is_array($value) ? $value : null;
    }

    public function storeSnapshot(string $mode, array $snapshot): void
    {
        $minutes = (int) config("maintenance.{$mode}_snapshot_ttl_minutes");
        Cache::put($this->snapshotKey($mode), $snapshot, now()->addMinutes($minutes));
    }

    public function status(): ?array
    {
        $value = Cache::get(self::STATUS_KEY);

        return is_array($value) ? $value : null;
    }

    public function putStatus(string $status, string $mode, array $extra = []): void
    {
        Cache::put(self::STATUS_KEY, [
            'status' => $status,
            'mode' => $mode,
            'updated_at' => now()->toIso8601String(),
            ...$extra,
        ], now()->addMinutes(60));
    }

    public function heartbeat(): ?string
    {
        $value = Cache::get(self::SCHEDULER_HEARTBEAT_KEY);

        return is_string($value) ? $value : null;
    }

    public function recordHeartbeat(): void
    {
        Cache::put(
            self::SCHEDULER_HEARTBEAT_KEY,
            now()->toIso8601String(),
            now()->addMinutes((int) config('maintenance.scheduler_heartbeat_ttl_minutes'))
        );
    }

    private function snapshotKey(string $mode): string
    {
        return match ($mode) {
            'quick' => self::QUICK_SNAPSHOT_KEY,
            'deep' => self::DEEP_SNAPSHOT_KEY,
            default => throw new \InvalidArgumentException('Mode maintenance tidak valid.'),
        };
    }
}
