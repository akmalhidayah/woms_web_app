<?php

namespace App\Http\Controllers\Admin\AppSheet;

use App\Exceptions\AppSheet\GoogleOAuthException;
use App\Exceptions\AppSheet\GoogleSheetsException;
use App\Http\Controllers\Controller;
use App\Services\AppSheet\AppSheetProfileDirectoryService;
use App\Services\AppSheet\GoogleDriveMediaService;
use App\Services\AppSheet\GoogleOAuthService;
use App\Services\AppSheet\GoogleSheetsReader;
use App\Support\AppSheet\ConsumableData;
use App\Support\AppSheet\DailyReportData;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DailyReportController extends Controller
{
    public function __invoke(
        Request $request,
        GoogleOAuthService $google,
        GoogleSheetsReader $reader,
        AppSheetProfileDirectoryService $profiles,
        GoogleDriveMediaService $driveMedia,
    ): View {
        $data = $this->loadSheet($google, fn (): array => $reader->dailyReports());
        $filters = [
            'search' => $this->filter($request, 'search'),
            'pic' => $this->filter($request, 'pic'),
            'year' => $this->filter($request, 'year'),
            'date' => $this->filter($request, 'date'),
        ];

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date']) || ConsumableData::date($filters['date']) === null) {
            $filters['date'] = '';
        }

        $allRows = collect($data['sheetRows'])
            ->filter(fn (array $row): bool => DailyReportData::isReportRow($row))
            ->values()
            ->map(fn (array $row, int $index): array => $this->prepareRow($row, $index));

        $picOptions = $allRows
            ->flatMap(fn (array $row): array => $row['_pic_names'])
            ->unique(fn (string $name): string => DailyReportData::nameKey($name))
            ->sort(fn (string $left, string $right): int => strnatcasecmp($left, $right))
            ->values();
        $years = $allRows->pluck('_year')->filter()->unique()->sortDesc()->values();

        if ($filters['pic'] !== '') {
            $selectedPic = $picOptions->first(
                fn (string $name): bool => DailyReportData::nameKey($name) === DailyReportData::nameKey($filters['pic']),
            );
            $filters['pic'] = is_string($selectedPic) ? $selectedPic : '';
        }
        if ($filters['year'] !== '' && ! $years->contains($filters['year'])) {
            $filters['year'] = '';
        }

        $filteredRows = $allRows->filter(function (array $row) use ($filters): bool {
            $matchesPic = $filters['pic'] === '' || in_array(
                DailyReportData::nameKey($filters['pic']),
                $row['_pic_keys'],
                true,
            );

            return ConsumableData::matchesSearch($row, [
                'ORDER', 'DESC. ORDER', 'INPUT NON ORDER', 'PROGRESS PEKERJAAN', 'PIC', 'INPUT BY',
            ], $filters['search'])
                && $matchesPic
                && ($filters['year'] === '' || $row['_year'] === $filters['year'])
                && ($filters['date'] === '' || $row['_date'] === $filters['date']);
        });

        $rows = $this->newestFirst($filteredRows);
        unset($data['sheetRows']);

        $paginatedRows = $this->paginate($rows, $request, $filters);
        $paginatedRows->setCollection($paginatedRows->getCollection()->map(
            fn (array $row): array => $this->decorateRow($row, $profiles, $driveMedia),
        ));

        return view('admin.appsheet.daily-report', $data + [
            'filters' => $filters,
            'picOptions' => $picOptions,
            'years' => $years,
            'rows' => $paginatedRows,
        ]);
    }

    private function prepareRow(array $row, int $sourceIndex): array
    {
        $date = ConsumableData::date($row['INPUT DATE'] ?? '');
        $picNames = DailyReportData::picNames($row['PIC'] ?? '');

        return $row + [
            '_order' => DailyReportData::inline($row['ORDER'] ?? ''),
            '_work' => DailyReportData::work($row),
            '_progress' => DailyReportData::multiline($row['PROGRESS PEKERJAAN'] ?? ''),
            '_pic_names' => $picNames,
            '_pic_keys' => array_map(fn (string $name): string => DailyReportData::nameKey($name), $picNames),
            '_input_by_name' => DailyReportData::inline($row['INPUT BY'] ?? ''),
            '_year' => DailyReportData::year($row['TAHUN'] ?? ''),
            '_date' => $date?->format('Y-m-d'),
            '_sort_timestamp' => ConsumableData::dateTimestamp($date),
            '_source_index' => $sourceIndex,
            '_date_display' => $date?->format('d/m/Y'),
            '_time_display' => $date !== null && $date->format('H:i:s') !== '00:00:00'
                ? $date->format('H:i:s')
                : null,
        ];
    }

    private function decorateRow(
        array $row,
        AppSheetProfileDirectoryService $profiles,
        GoogleDriveMediaService $driveMedia,
    ): array {
        $picProfiles = array_map(function (string $name) use ($profiles, $driveMedia): array {
            $profile = $profiles->resolve($name);

            return $profile + [
                'avatar_url' => $driveMedia->mediaUrl(
                    GoogleDriveMediaService::REQUESTER_COLLECTION,
                    $profile['image_path'],
                ),
            ];
        }, $row['_pic_names']);

        $inputBy = null;
        if ($row['_input_by_name'] !== '') {
            $inputBy = $profiles->resolve($row['_input_by_name']);
            $inputBy += [
                'avatar_url' => $driveMedia->mediaUrl(
                    GoogleDriveMediaService::REQUESTER_COLLECTION,
                    $inputBy['image_path'],
                ),
            ];
        }

        return $row + [
            '_pic_profiles' => $picProfiles,
            '_input_by' => $inputBy,
            '_photo_thumbnail_url' => $driveMedia->mediaUrl(
                GoogleDriveMediaService::DAILY_REPORT_COLLECTION,
                $row['POTO PEKERJAAN'] ?? '',
                GoogleDriveMediaService::VARIANT_THUMB,
            ),
            '_photo_preview_url' => $driveMedia->mediaUrl(
                GoogleDriveMediaService::DAILY_REPORT_COLLECTION,
                $row['POTO PEKERJAAN'] ?? '',
                GoogleDriveMediaService::VARIANT_PREVIEW,
            ),
            // Lampiran hanya diberikan bila path-nya juga lolos allowlist folder gambar laporan.
            '_attachment_url' => $driveMedia->mediaUrl(
                GoogleDriveMediaService::DAILY_REPORT_COLLECTION,
                $row['ATTACHMENT'] ?? '',
                GoogleDriveMediaService::VARIANT_PREVIEW,
            ),
        ];
    }

    private function newestFirst(Collection $rows): Collection
    {
        return $rows->sort(function (array $left, array $right): int {
            $leftTimestamp = $left['_sort_timestamp'];
            $rightTimestamp = $right['_sort_timestamp'];

            if ($leftTimestamp === null || $rightTimestamp === null) {
                if ($leftTimestamp !== $rightTimestamp) {
                    return $leftTimestamp === null ? 1 : -1;
                }
            } else {
                $timestampOrder = $rightTimestamp <=> $leftTimestamp;
                if ($timestampOrder !== 0) {
                    return $timestampOrder;
                }
            }

            return $left['_source_index'] <=> $right['_source_index'];
        })->values();
    }

    private function loadSheet(GoogleOAuthService $google, Closure $read): array
    {
        $data = ['googleConnected' => false, 'googleConnectionError' => null, 'sheetError' => null, 'sheetRows' => []];

        try {
            $data['googleConnected'] = $google->isConnected();
            if ($data['googleConnected']) {
                $data['sheetRows'] = $read();
            }
        } catch (GoogleOAuthException) {
            $data['googleConnected'] = false;
            $data['googleConnectionError'] = 'Koneksi Google perlu diperbarui.';
        } catch (GoogleSheetsException $exception) {
            if ($exception->requiresReconnect) {
                $data['googleConnected'] = false;
                $data['googleConnectionError'] = 'Koneksi Google perlu diperbarui.';
            } else {
                $data['sheetError'] = $exception->getMessage();
            }
        }

        return $data;
    }

    private function filter(Request $request, string $name): string
    {
        $value = $request->query($name);

        return is_string($value) ? mb_substr(trim($value), 0, 200) : '';
    }

    private function paginate(Collection $rows, Request $request, array $filters): LengthAwarePaginator
    {
        $perPage = 25;
        $page = max(1, min((int) $this->filter($request, 'page'), max(1, (int) ceil($rows->count() / $perPage))));

        return new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page, [
            'path' => $request->url(),
            'query' => array_filter($filters, fn (string $value): bool => $value !== ''),
        ]);
    }
}
