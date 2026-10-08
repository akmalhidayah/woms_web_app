<?php

namespace App\Services\AppSheet;

use App\Exceptions\AppSheet\GoogleSheetsException;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppSheetProfileDirectoryService
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $directory = null;

    public function __construct(private readonly GoogleSheetsReader $reader) {}

    /**
     * @return array{name: string, position: string, regu: string, shift: string, image_path: string, initials: string}
     */
    public function resolve(mixed $requesterName): array
    {
        $name = $this->clean($requesterName);
        $profile = $this->profiles()[$this->key($name)] ?? [];

        return [
            'name' => $name !== '' ? $name : 'Tidak diketahui',
            'position' => $this->clean($profile['JABATAN'] ?? ''),
            'regu' => $this->clean($profile['REGU'] ?? ''),
            'shift' => $this->clean($profile['SHIFT'] ?? ''),
            'image_path' => $this->clean($profile['IMG'] ?? ''),
            'initials' => $this->initials($name),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function profiles(): array
    {
        if ($this->directory !== null) {
            return $this->directory;
        }

        try {
            $rows = $this->reader->requesterProfiles();
        } catch (GoogleSheetsException $exception) {
            Log::notice('AppSheet requester profile directory unavailable.', [
                'requires_reconnect' => $exception->requiresReconnect,
            ]);

            return $this->directory = [];
        } catch (Throwable $exception) {
            Log::warning('AppSheet requester profile directory failed.', [
                'exception_type' => $exception::class,
            ]);

            return $this->directory = [];
        }

        $this->directory = [];
        foreach ($rows as $row) {
            $key = $this->key($row['NAMA'] ?? '');
            if ($key !== '' && ! array_key_exists($key, $this->directory)) {
                $this->directory[$key] = $row;
            }
        }

        return $this->directory;
    }

    private function key(mixed $value): string
    {
        return mb_strtolower($this->clean($value));
    }

    private function clean(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        return preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
    }

    private function initials(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_substr($word, 0, 1);
        }

        return mb_strtoupper($initials !== '' ? $initials : '?');
    }
}
