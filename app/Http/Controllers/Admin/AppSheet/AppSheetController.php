<?php

namespace App\Http\Controllers\Admin\AppSheet;

use App\Exceptions\AppSheet\GoogleOAuthException;
use App\Exceptions\AppSheet\GoogleSheetsException;
use App\Http\Controllers\Controller;
use App\Services\AppSheet\GoogleOAuthService;
use App\Services\AppSheet\GoogleSheetsReader;
use App\Support\AppSheet\ConsumableData;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AppSheetController extends Controller
{
    public function historyConsumable(Request $request, GoogleOAuthService $google, GoogleSheetsReader $reader): View
    {
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

        return view('admin.appsheet.history-consumable', $data + [
            'filters' => $filters,
            'categories' => $categories,
            'rows' => $this->paginate($rows, $request, $filters),
            'totalRows' => $allRows->count(),
        ]);
    }

    public function stockConsumable(Request $request, GoogleOAuthService $google, GoogleSheetsReader $reader): View
    {
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
        $rows = $this->newestFirst($allRows->filter(fn (array $row): bool => ConsumableData::matchesSearch($row, ['UID', 'TYPE CATEGORY', 'DESC.', 'SUB CATEGORY', 'LOC', 'STN'], $filters['search'])
            && ($filters['jenis'] === '' || trim((string) $row['SUB CATEGORY']) === $filters['jenis'])
            && ($filters['status'] === '' || $row['_stock_status'] === $filters['status'])
        ));
        unset($data['sheetRows']);

        return view('admin.appsheet.stock-consumable', $data + [
            'filters' => $filters,
            'subCategories' => $subCategories,
            'rows' => $this->paginate($rows, $request, $filters),
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
            '_date_display' => ConsumableData::displayDate($dateValue),
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

    private function paginate(Collection $rows, Request $request, array $filters): LengthAwarePaginator
    {
        $perPage = 50;
        $page = max(1, min((int) $this->filter($request, 'page'), max(1, (int) ceil($rows->count() / $perPage))));

        return new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page, [
            'path' => $request->url(),
            'query' => array_filter($filters, fn (string $value) => $value !== ''),
        ]);
    }
}
