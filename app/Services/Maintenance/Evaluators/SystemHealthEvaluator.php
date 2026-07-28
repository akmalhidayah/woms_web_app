<?php

namespace App\Services\Maintenance\Evaluators;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemHealthEvaluator implements MaintenanceEvaluator
{
    public function category(): string
    {
        return 'system';
    }

    public function evaluate(string $mode): array
    {
        $findings = [];
        DB::select('select 1');

        $cacheKey = 'woms:maintenance:probe:'.uniqid();
        Cache::put($cacheKey, 'ok', 30);
        if (Cache::get($cacheKey) !== 'ok') {
            $findings[] = $this->finding('cache_unavailable', 'critical', 'Cache tidak dapat dibaca atau ditulis.');
        }
        Cache::forget($cacheKey);

        $path = 'maintenance-probe/'.uniqid().'.txt';
        try {
            Storage::disk('local')->put($path, 'ok');
            if (! Storage::disk('local')->exists($path)) {
                $findings[] = $this->finding('storage_unavailable', 'critical', 'Storage lokal tidak dapat dibaca atau ditulis.');
            }
        } finally {
            Storage::disk('local')->delete($path);
        }

        return $findings;
    }

    private function finding(string $code, string $severity, string $description): array
    {
        return [
            'code' => $code,
            'severity' => $severity,
            'module' => 'Sistem',
            'title' => 'Pemeriksaan infrastruktur',
            'description' => $description,
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
