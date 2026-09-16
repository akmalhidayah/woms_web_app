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
use App\Support\AppSheet\StockData;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AppSheetController extends Controller
{
    public function historyConsumable(
        Request $request,
        GoogleOAuthService $google,
        GoogleSheetsReader $reader,
        AppSheetProfileDirectoryService $profiles,
        GoogleDriveMediaService $driveMedia,
    ): View {
        $data = $this->loadSheet($google, fn () => $reader->historyConsumable());
        $filters = [
            'search' => $this->filter($request, 'search'),
            'input_type' => $this->filter($request, 'input_type'),
            'category' => $this->filter($request, 'category'),
            'date' => $this->filter($request, 'date'),
        ];
        if (! in_array($filters['input_type'], ['STOCK IN', 'STOCK OUT'], true)) {
            $filters['input_type'] = '';
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date']) || ConsumableData::date($filters['date']) === null) {
            $filters['date'] = '';
        }

        $allRows = collect($data['sheetRows'])
            ->values()
            ->map(fn (array $row, int $index): array => $this->withInputDateMetadata($row, $index));
        $categories = $this->options($allRows, 'CATEGORY');
        $rows = $this->newestFirst($allRows->filter(fn (array $row): bool => ConsumableData::matchesSearch($row, ['UID', 'DESC.', 'INPUT BY', 'TUJUAN PENGGUNAAN'], $filters['search'])
            && ($filters['input_type'] === '' || mb_strtoupper(trim((string) $row['INPUT TYPE'])) === $filters['input_type'])
            && ($filters['category'] === '' || trim((string) $row['CATEGORY']) === $filters['category'])
            && ($filters['date'] === '' || $row['_date'] === $filters['date'])
        ));
        unset($data['sheetRows']);
        $paginatedRows = $this->paginate($rows, $request, $filters, 25);
        $paginatedRows->setCollection($paginatedRows->getCollection()->map(function (array $row) use ($profiles, $driveMedia): array {
            $requester = $profiles->resolve($row['INPUT BY'] ?? '');

            return $row + [
                '_requester' => $requester,
                '_requester_avatar_url' => $driveMedia->mediaUrl(
                    GoogleDriveMediaService::REQUESTER_COLLECTION,
                    $requester['image_path'],
                ),
            ];
        }));

        return view('admin.appsheet.history-consumable', $data + [
            'filters' => $filters,
            'categories' => $categories,
            'rows' => $paginatedRows,
            'totalRows' => $allRows->count(),
        ]);
    }

    public function stockConsumable(
        Request $request,
        GoogleOAuthService $google,
        GoogleSheetsReader $reader,
        GoogleDriveMediaService $driveMedia,
    ): View {
        $data = $this->loadSheet($google, fn () => $reader->stockConsumable());
        $filters = [
            'search' => $this->filter($request, 'search'),
            'jenis' => $this->filter($request, 'jenis'),
            'status' => $this->filter($request, 'status'),
        ];
        if (! in_array($filters['status'], ['habis', 'tersedia'], true)) {
            $filters['status'] = '';
        }

        $allRows = collect($data['sheetRows'])
            ->filter(fn (array $row): bool => mb_strtoupper(trim((string) ($row['CATEGORY'] ?? ''))) === 'CONSUMABLE')
            ->values()
            ->map(function (array $row, int $index): array {
                return $this->withInputDateMetadata($row, $index) + [
                    '_stock_status' => ConsumableData::stockStatus($row),
                ];
            });
        $subCategories = $this->options($allRows, 'SUB CATEGORY');
        $rows = $this->uidAscending($allRows->filter(fn (array $row): bool => ConsumableData::matchesSearch($row, ['UID', 'TYPE CATEGORY', 'DESC.', 'SIZE', 'SUB CATEGORY', 'LOC', 'STN'], $filters['search'])
            && ($filters['jenis'] === '' || trim((string) $row['SUB CATEGORY']) === $filters['jenis'])
            && ($filters['status'] === '' || $row['_stock_status'] === $filters['status'])
        ));
        unset($data['sheetRows']);
        $paginatedRows = $this->paginate($rows, $request, $filters, 25);
        $paginatedRows->setCollection($paginatedRows->getCollection()->map(fn (array $row): array => $row + [
            '_image_url' => $driveMedia->mediaUrl(
                GoogleDriveMediaService::STOCK_COLLECTION,
                $row['IMG'] ?? '',
            ),
            '_image_preview_url' => $driveMedia->mediaUrl(
                GoogleDriveMediaService::STOCK_COLLECTION,
                $row['IMG'] ?? '',
                GoogleDriveMediaService::VARIANT_DISPLAY,
            ),
        ]));

        return view('admin.appsheet.stock-consumable', $data + [
            'filters' => $filters,
            'subCategories' => $subCategories,
            'rows' => $paginatedRows,
            'totalRows' => $allRows->count(),
        ]);
    }

    public function stockConsumableGudang(Request $request, GoogleOAuthService $google, GoogleSheetsReader $reader): View
    {
        return $this->stockSheet(
            $request,
            $google,
            fn (): array => array_map(StockData::consumableGudang(...), $reader->stockConsumableGudang()),
            'consumable-gudang',
        );
    }

    public function stockMaterialBms(Request $request, GoogleOAuthService $google, GoogleSheetsReader $reader): View
    {
        return $this->stockSheet(
            $request,
            $google,
            fn (): array => array_map(StockData::materialBms(...), $reader->stockMaterialBms()),
            'material-bms',
        );
    }

    public function stockMaterialGudang(Request $request, GoogleOAuthService $google, GoogleSheetsReader $reader): View
    {
        return $this->stockSheet(
            $request,
            $google,
            fn (): array => array_map(StockData::materialGudang(...), $reader->stockMaterialGudang()),
            'material-gudang',
        );
    }

    private function stockSheet(Request $request, GoogleOAuthService $google, Closure $read, string $stockKind): View
    {
        $data = $this->loadSheet($google, $read);
        $isMaterial = $stockKind !== 'consumable-gudang';
        $typeFilter = match ($stockKind) {
            'consumable-gudang' => 'jenis',
            'material-gudang' => 'mrp_type',
            default => null,
        };
        $filters = ['search' => $this->filter($request, 'search')];
        if ($typeFilter !== null) {
            $filters[$typeFilter] = $this->filter($request, $typeFilter);
        }
        if ($isMaterial) {
            $filters['location'] = $this->filter($request, 'location');
            $filters['status'] = $this->filter($request, 'status');
            if (! in_array($filters['status'], ['habis', 'tersedia'], true)) {
                $filters['status'] = '';
            }
        }

        $allRows = collect($data['sheetRows'])->filter(StockData::hasItem(...))->values()
            ->map(fn (array $row, int $index): array => $row + ['_source_index' => $index]);
        $types = $this->options($allRows, 'type');
        $locations = $this->options($allRows, 'location');
        $filteredRows = $allRows->filter(fn (array $row): bool => ConsumableData::matchesSearch(
            $row, ['code', 'name', 'type', 'description', 'location', 'updated_by'], $filters['search'],
        )
            && ($typeFilter === null || $filters[$typeFilter] === '' || $row['type'] === $filters[$typeFilter])
            && (! $isMaterial || $filters['location'] === '' || $row['location'] === $filters['location'])
            && (! $isMaterial || $filters['status'] === '' || $row['status'] === $filters['status'])
        );
        $rows = ($stockKind === 'consumable-gudang'
            ? $filteredRows->sort(function (array $left, array $right): int {
                $leftTimestamp = $left['date_timestamp'];
                $rightTimestamp = $right['date_timestamp'];

                if ($leftTimestamp === null || $rightTimestamp === null) {
                    if ($leftTimestamp !== $rightTimestamp) {
                        return $leftTimestamp === null ? 1 : -1;
                    }
                } else {
                    $dateOrder = $rightTimestamp <=> $leftTimestamp;
                    if ($dateOrder !== 0) {
                        return $dateOrder;
                    }
                }

                return $left['_source_index'] <=> $right['_source_index'];
            })
            : $filteredRows->sort(function (array $left, array $right): int {
                // Kode kosong/tanda '-' tetap tampil, sesudah item berkode; urutan setara tetap stabil.
                $missingOrder = (int) in_array($left['code'], ['', '-'], true)
                    <=> (int) in_array($right['code'], ['', '-'], true);
                $codeOrder = strnatcasecmp($left['code'], $right['code']);

                return $missingOrder ?: ($codeOrder ?: $left['_source_index'] <=> $right['_source_index']);
            }))->values();
        unset($data['sheetRows']);

        return view('admin.appsheet.stock-sheet', $data + [
            'stockKind' => $stockKind,
            'isMaterial' => $isMaterial,
            'typeFilter' => $typeFilter,
            'filters' => $filters,
            'types' => $types,
            'locations' => $locations,
            'rows' => $this->paginate($rows, $request, $filters, 25),
            'totalRows' => $allRows->count(),
        ]);
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

    private function options(Collection $rows, string $header): Collection
    {
        return $rows->pluck($header)->map(fn ($value) => trim((string) $value))
            ->filter(fn (string $value) => $value !== '')->unique()->sort()->values();
    }

    private function withInputDateMetadata(array $row, int $sourceIndex): array
    {
        $dateValue = $row['INPUT DATE'] ?? '';
        $date = ConsumableData::date($dateValue);

        return $row + [
            '_date' => $date?->format('Y-m-d'),
            '_sort_timestamp' => ConsumableData::dateTimestamp($date),
            '_source_index' => $sourceIndex,
            '_date_display' => $date?->format('d/m/Y') ?? ConsumableData::displayDate($dateValue),
            '_time_display' => $date !== null && $date->format('H:i:s') !== '00:00:00'
                ? $date->format('H:i:s')
                : null,
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

    private function uidAscending(Collection $rows): Collection
    {
        return $rows->sort(function (array $left, array $right): int {
            $uidOrder = strnatcasecmp(
                trim((string) ($left['UID'] ?? '')),
                trim((string) ($right['UID'] ?? '')),
            );

            return $uidOrder !== 0
                ? $uidOrder
                : $left['_source_index'] <=> $right['_source_index'];
        })->values();
    }

    private function paginate(Collection $rows, Request $request, array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $page = max(1, min((int) $this->filter($request, 'page'), max(1, (int) ceil($rows->count() / $perPage))));

        return new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page, [
            'path' => $request->url(),
            'query' => array_filter($filters, fn (string $value) => $value !== ''),
        ]);
    }
}
