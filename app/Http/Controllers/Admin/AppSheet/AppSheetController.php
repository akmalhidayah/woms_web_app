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

        $allRows = collect($data['sheetRows'])->map(function (array $row): array {
            $date = ConsumableData::date($row['INPUT DATE']);

            return $row + [
                '_date' => $date?->format('Y-m-d'),
                '_sort' => $date?->getTimestamp() ?? PHP_INT_MIN,
                '_date_display' => ConsumableData::displayDate($row['INPUT DATE']),
            ];
        });
        $categories = $this->options($allRows, 'CATEGORY');
        $rows = $allRows->filter(fn (array $row): bool =>
            ConsumableData::matchesSearch($row, ['UID', 'DESC.', 'INPUT BY', 'TUJUAN PENGGUNAAN'], $filters['search'])
            && ($filters['input_type'] === '' || mb_strtoupper(trim((string) $row['INPUT TYPE'])) === $filters['input_type'])
            && ($filters['category'] === '' || trim((string) $row['CATEGORY']) === $filters['category'])
            && ($filters['date'] === '' || $row['_date'] === $filters['date'])
        )->sortByDesc('_sort')->values();
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
        if (! in_array($filters['status'], ['habis', 'rendah', 'aman'], true)) {
            $filters['status'] = '';
        }

        $allRows = collect($data['sheetRows'])->map(fn (array $row): array => $row + [
            '_stock_status' => ConsumableData::stockStatus($row),
            '_date_display' => ConsumableData::displayDate($row['UPD. DATE']),
        ]);
        $types = $this->options($allRows, 'JENIS CONSUMABLE');
        $rows = $allRows->filter(fn (array $row): bool =>
            ConsumableData::matchesSearch($row, ['NO MATERIAL', 'CONSUMABLE', 'DESKRIPSI'], $filters['search'])
            && ($filters['jenis'] === '' || trim((string) $row['JENIS CONSUMABLE']) === $filters['jenis'])
            && ($filters['status'] === '' || $row['_stock_status'] === $filters['status'])
        )->values();
        unset($data['sheetRows']);

        return view('admin.appsheet.stock-consumable', $data + [
            'filters' => $filters,
            'types' => $types,
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
