<?php

namespace Tests\Unit\Maintenance;

use App\Services\Maintenance\LaravelLogReader;
use Tests\TestCase;

class LaravelLogReaderTest extends TestCase
{
    public function test_it_returns_last_ten_entries_and_redacts_sensitive_values(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'woms-log-');
        $lines = [];
        foreach (range(1, 12) as $index) {
            $lines[] = sprintf(
                '[2026-07-28 15:%02d:00] production.INFO: Log %d token=secret-%d password=hidden',
                $index,
                $index,
                $index
            );
        }
        file_put_contents($path, implode("\n", $lines));

        try {
            $logs = (new LaravelLogReader($path))->latest(10);

            $this->assertCount(10, $logs);
            $this->assertStringContainsString('Log 3', $logs[0]['message']);
            $this->assertStringContainsString('token=[REDACTED]', $logs[0]['message']);
            $this->assertStringNotContainsString('secret-3', json_encode($logs));
            $this->assertStringNotContainsString('hidden', json_encode($logs));
        } finally {
            unlink($path);
        }
    }
}
