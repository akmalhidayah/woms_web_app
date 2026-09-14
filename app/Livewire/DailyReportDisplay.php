<?php

namespace App\Livewire;

use App\Models\BengkelDisplaySetting;
use App\Services\AppSheet\AppSheetProfileDirectoryService;
use App\Services\AppSheet\GoogleDriveMediaService;
use App\Services\AppSheet\GoogleOAuthService;
use App\Services\AppSheet\GoogleSheetsReader;
use App\Support\AppSheet\ConsumableData;
use App\Support\AppSheet\DailyReportData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class DailyReportDisplay extends Component
{
    public const DISPLAY_PER_PAGE = 6;

    private const SNAPSHOT_CACHE_KEY = 'appsheet:daily-report-display:rows:v1';

    private const SNAPSHOT_TTL_SECONDS = 60;

    private const POLL_SECONDS = 5;

    private const SLIDE_SECONDS = 30;

    /**
     * @var array<int, array<int, array<string, mixed>>>
     */
    public array $pages = [];

    public int $pageSlide = 0;

    public int $maxPages = 1;

    public int $totalReports = 0;

    public int $slidePollCounter = 0;

    public int $snapshotPollCounter = 0;

    public bool $snapshotFailed = false;

    public string $tickerText = '';

    public int $tickerSpeedSeconds = 18;

    public function mount(): void
    {
        $this->loadDisplaySettings();
        $this->loadSnapshot();
    }

    public function tickDisplay(): void
    {
        $this->snapshotPollCounter++;
        $this->slidePollCounter++;

        if ($this->snapshotPollCounter >= (int) (self::SNAPSHOT_TTL_SECONDS / self::POLL_SECONDS)) {
            $this->snapshotPollCounter = 0;
            $this->loadDisplaySettings();
            $this->loadSnapshot();
        }

        if ($this->slidePollCounter >= (int) (self::SLIDE_SECONDS / self::POLL_SECONDS)) {
            $this->slidePollCounter = 0;
            $this->nextSlide();
        }
    }

    public function nextSlide(): void
    {
        if ($this->maxPages <= 1 || $this->pageSlide >= $this->maxPages - 1) {
            $this->pageSlide = 0;
            $this->dispatch('daily-report-display-cycle-completed');

            return;
        }

        $this->pageSlide++;
    }

    public function render()
    {
        return view('livewire.daily-report-display');
    }

    private function loadSnapshot(): void
    {
        try {
            $cache = Cache::store('file');
            $snapshot = $cache->remember(
                self::SNAPSHOT_CACHE_KEY,
                self::SNAPSHOT_TTL_SECONDS,
                fn (): array => $this->buildSnapshot(),
            );

            if (! is_array($snapshot) || ! is_array($snapshot['rows'] ?? null)) {
                $snapshot = ['rows' => [], 'failed' => true];
            }
        } catch (Throwable $exception) {
            Log::warning('Daily Report TV snapshot unavailable.', [
                'exception_type' => $exception::class,
            ]);
            $snapshot = ['rows' => [], 'failed' => true];
        }

        $driveMedia = app(GoogleDriveMediaService::class);
        $rows = collect($snapshot['rows'])
            ->map(function (array $row) use ($driveMedia): array {
                $row['photo_url'] = $driveMedia->dailyReportDisplayMediaUrl($row['photo_path'] ?? '');
                unset($row['photo_path']);

                return $row;
            })
            ->values();

        $this->pages = $rows
            ->chunk(self::DISPLAY_PER_PAGE)
            ->map(fn ($page): array => $page->values()->all())
            ->values()
            ->all();
        $this->totalReports = $rows->count();
        $this->maxPages = max(1, count($this->pages));
        $this->pageSlide %= $this->maxPages;
        $this->snapshotFailed = (bool) ($snapshot['failed'] ?? false);
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, failed: bool}
     */
    private function buildSnapshot(): array
    {
        try {
            $google = app(GoogleOAuthService::class);
            if (! $google->isConnected()) {
                return ['rows' => [], 'failed' => true];
            }

            $reader = app(GoogleSheetsReader::class);
            $profiles = app(AppSheetProfileDirectoryService::class);
            $today = now()->startOfDay();
            $startDate = $today->copy()->subDays(2)->toDateString();
            $endDate = $today->toDateString();

            $rows = collect($reader->dailyReports())
                ->filter(fn (array $row): bool => DailyReportData::isReportRow($row))
                ->values()
                ->map(function (array $row, int $sourceIndex) use ($profiles, $startDate, $endDate): ?array {
                    $date = ConsumableData::date($row['INPUT DATE'] ?? '');
                    if ($date === null) {
                        return null;
                    }

                    $dateKey = $date->format('Y-m-d');
                    if ($dateKey < $startDate || $dateKey > $endDate) {
                        return null;
                    }

                    $work = DailyReportData::work($row);
                    $picProfiles = collect(DailyReportData::picNames($row['PIC'] ?? ''))
                        ->map(function (string $name) use ($profiles): array {
                            $profile = $profiles->resolve($name);

                            return [
                                'name' => $profile['name'],
                                'initials' => $profile['initials'],
                            ];
                        })
                        ->values()
                        ->all();
                    $time = $date->format('H:i:s') === '00:00:00' ? null : $date->format('H:i');

                    return [
                        'id' => hash('sha256', $sourceIndex."\0".$date->format('c')."\0".$work['title']),
                        'order' => DailyReportData::inline($row['ORDER'] ?? ''),
                        'title' => $work['title'],
                        'progress' => DailyReportData::multiline($row['PROGRESS PEKERJAAN'] ?? ''),
                        'pic_profiles' => $picProfiles,
                        'date' => $this->dateLabel($date),
                        'time' => $time,
                        'sort_timestamp' => $date->getTimestamp(),
                        'source_index' => $sourceIndex,
                        'photo_path' => $row['POTO PEKERJAAN'] ?? $row['FOTO PEKERJAAN'] ?? '',
                    ];
                })
                ->filter(fn ($row): bool => is_array($row))
                ->sort(function (array $left, array $right): int {
                    $timestampOrder = $right['sort_timestamp'] <=> $left['sort_timestamp'];

                    return $timestampOrder !== 0
                        ? $timestampOrder
                        : $left['source_index'] <=> $right['source_index'];
                })
                ->map(function (array $row): array {
                    unset($row['sort_timestamp'], $row['source_index']);

                    return $row;
                })
                ->values()
                ->all();

            return ['rows' => $rows, 'failed' => false];
        } catch (Throwable $exception) {
            Log::warning('Daily Report TV data unavailable.', [
                'exception_type' => $exception::class,
            ]);

            return ['rows' => [], 'failed' => true];
        }
    }

    private function loadDisplaySettings(): void
    {
        $setting = BengkelDisplaySetting::current();

        $this->tickerText = trim((string) ($setting->ticker_text ?? ''));
        $this->tickerSpeedSeconds = max(5, min(60, (int) ($setting->ticker_speed_seconds ?? 18)));
    }

    private function dateLabel(\DateTimeImmutable $date): string
    {
        $months = [
            1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR',
            5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGU',
            9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES',
        ];

        return $date->format('d').' '.$months[(int) $date->format('n')].' '.$date->format('Y');
    }
}
