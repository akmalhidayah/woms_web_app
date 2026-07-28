<?php

namespace App\Services\Maintenance;

use Illuminate\Support\Str;

class LaravelLogReader
{
    private const MAX_READ_BYTES = 262144;

    public function __construct(private readonly ?string $path = null) {}

    /**
     * @return list<array{timestamp: string, environment: string, level: string, message: string}>
     */
    public function latest(int $limit = 10): array
    {
        $path = $this->path ?? $this->resolveLatestLogPath();
        if (! $path || ! is_file($path) || ! is_readable($path)) {
            return [];
        }

        $contents = $this->readTail($path);
        preg_match_all(
            '/^\\[(?<timestamp>[^\\]]+)]\\s+(?<environment>[\\w-]+)\\.(?<level>[A-Z]+):\\s*(?<message>.*)$/m',
            $contents,
            $matches,
            PREG_SET_ORDER
        );

        return collect($matches)
            ->take(-max(1, min($limit, 50)))
            ->map(fn (array $match): array => [
                'timestamp' => $match['timestamp'],
                'environment' => $match['environment'],
                'level' => strtolower($match['level']),
                'message' => $this->sanitize($match['message']),
            ])
            ->values()
            ->all();
    }

    private function resolveLatestLogPath(): ?string
    {
        $paths = glob(storage_path('logs/laravel*.log')) ?: [];
        if ($paths === []) {
            return null;
        }

        usort($paths, fn (string $left, string $right): int => filemtime($right) <=> filemtime($left));

        return $paths[0];
    }

    private function readTail(string $path): string
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return '';
        }

        try {
            $size = filesize($path) ?: 0;
            $offset = max(0, $size - self::MAX_READ_BYTES);
            fseek($handle, $offset);
            $contents = stream_get_contents($handle) ?: '';

            if ($offset > 0 && ($firstNewline = strpos($contents, "\n")) !== false) {
                $contents = substr($contents, $firstNewline + 1);
            }

            return $contents;
        } finally {
            fclose($handle);
        }
    }

    private function sanitize(string $message): string
    {
        $message = str_replace(
            array_filter([base_path(), storage_path()]),
            '[APP_PATH]',
            $message
        );
        $message = preg_replace(
            "/\\b(password|passwd|token|token_hash|secret|authorization|cookie|api[_-]?key)\\b\\s*[=:]\\s*(?:\"[^\"]*\"|'[^']*'|[^\\s,}\\]]+)/i",
            '$1=[REDACTED]',
            $message
        ) ?? $message;
        $message = preg_replace('/([?&](?:token|key|secret|signature)=)[^&\\s]+/i', '$1[REDACTED]', $message) ?? $message;

        return Str::limit(trim($message), 500);
    }
}
