<?php

namespace App\Services\AppSheet;

use App\Exceptions\AppSheet\GoogleOAuthException;
use App\Exceptions\AppSheet\GoogleSheetsException;
use App\Support\AppSheet\ConsumableData;
use App\Support\AppSheet\StockData;
use App\Support\AppSheet\StockSheetMap;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SensitiveParameter;
use Throwable;

class GoogleSheetsWriter
{
    public const TRANSACTION_STOCK_IN = 'STOCK IN';

    public const TRANSACTION_STOCK_OUT = 'STOCK OUT';

    public function __construct(private readonly GoogleOAuthService $google) {}

    /** @return list<string> */
    public static function transactionTypes(): array
    {
        return [self::TRANSACTION_STOCK_IN, self::TRANSACTION_STOCK_OUT];
    }

    /**
     * @return array{input_type: string, item_name: string, quantity: float, unit: string, stock_after: float}
     */
    public function createConsumableTransaction(
        string $identifier,
        string $inputType,
        int|float|string $quantity,
        ?string $usagePurpose,
        ?string $requestType,
        string $actor,
        string $transactionToken,
    ): array {
        try {
            if (! $this->google->hasSheetsWriteScope()) {
                throw new GoogleSheetsException(
                    'Koneksi Google belum memiliki izin edit Google Sheets. Silakan Hubungkan Ulang Google.',
                    true,
                );
            }

            $spreadsheetId = config('services.google.spreadsheet_id');
            $stockSheet = config('services.google.stock_consumable_sheet');
            $historySheet = config('services.google.history_consumable_sheet');
            if (! is_string($spreadsheetId) || trim($spreadsheetId) === ''
                || ! is_string($stockSheet) || trim($stockSheet) === ''
                || ! is_string($historySheet) || trim($historySheet) === '') {
                throw new GoogleSheetsException('Konfigurasi sheet History atau Stock Consumable belum lengkap. Hubungi pengelola aplikasi.');
            }

            $identifier = trim($identifier);
            $inputType = mb_strtoupper(trim($inputType));
            $parsedQuantity = ConsumableData::number($quantity);
            if ($identifier === '') {
                throw new GoogleSheetsException('Barang tidak ditemukan pada data stock terbaru.');
            }
            if (! in_array($inputType, self::transactionTypes(), true)) {
                throw new GoogleSheetsException('Jenis transaksi hanya boleh STOCK IN atau STOCK OUT.');
            }
            if ($parsedQuantity === null || $parsedQuantity <= 0) {
                throw new GoogleSheetsException('Jumlah transaksi harus berupa angka valid dan lebih besar dari 0.');
            }

            $token = $this->google->accessToken();
            $cache = Cache::store('file');
            $fingerprint = hash('sha256', json_encode([
                $identifier, $inputType, $parsedQuantity, $usagePurpose, $requestType, $actor,
            ], JSON_THROW_ON_ERROR));
            $resultCacheKey = 'appsheet:consumable-transaction-result:'.hash('sha256', $transactionToken);

            return $cache->lock('appsheet:stock-write:'.StockSheetMap::CONSUMABLE_BMS, 30)->block(5, function () use (
                $cache,
                $resultCacheKey,
                $fingerprint,
                $spreadsheetId,
                $stockSheet,
                $historySheet,
                $token,
                $identifier,
                $inputType,
                $parsedQuantity,
                $usagePurpose,
                $requestType,
                $actor,
            ): array {
                $previous = $cache->get($resultCacheKey);
                if (is_array($previous) && ($previous['fingerprint'] ?? null) === $fingerprint
                    && is_array($previous['result'] ?? null)) {
                    return $previous['result'];
                }
                if (is_array($previous)) {
                    throw new GoogleSheetsException('Form transaksi sudah pernah digunakan. Silakan muat ulang halaman.');
                }

                $result = $this->createFreshConsumableTransaction(
                    $spreadsheetId,
                    $stockSheet,
                    $historySheet,
                    $token,
                    $identifier,
                    $inputType,
                    $parsedQuantity,
                    $usagePurpose,
                    $requestType,
                    $actor,
                );

                $cache->forget('appsheet:history-consumable');
                $cache->forget('appsheet:stock-consumable');
                $cache->put($resultCacheKey, ['fingerprint' => $fingerprint, 'result' => $result], now()->addDay());

                return $result;
            });
        } catch (GoogleOAuthException) {
            throw new GoogleSheetsException('Koneksi Google perlu diperbarui. Silakan Hubungkan Ulang Google.', true);
        } catch (GoogleSheetsException $exception) {
            throw $exception;
        } catch (LockTimeoutException) {
            throw new GoogleSheetsException('Stock sedang diperbarui oleh pengguna lain. Silakan coba kembali sebentar lagi.');
        } catch (Throwable $exception) {
            // Jangan mencatat token, header Authorization, payload, atau respons Google.
            Log::warning('AppSheet consumable transaction failed.', ['exception_type' => $exception::class]);

            throw new GoogleSheetsException('Transaksi belum dapat disimpan ke Google Sheets. Silakan coba kembali.');
        }
    }

    /**
     * @param  array<string, int|float|string>  $submittedValues
     * @param  array<string, int|float|string>  $originalValues
     * @return array{changed: bool, item_name: string, changes: array<string, array{old: float, new: float}>}
     */
    public function updateStock(
        string $stockKind,
        string $identifier,
        array $submittedValues,
        array $originalValues,
        string $actor,
    ): array {
        try {
            if (! $this->google->hasSheetsWriteScope()) {
                throw new GoogleSheetsException(
                    'Koneksi Google belum memiliki izin edit Google Sheets. Silakan Hubungkan Ulang Google.',
                    true,
                );
            }

            $definition = StockSheetMap::definition($stockKind);
            $spreadsheetId = config('services.google.spreadsheet_id');
            $sheet = config('services.google.'.$definition['sheet_config']);
            if (! is_string($spreadsheetId) || trim($spreadsheetId) === ''
                || ! is_string($sheet) || trim($sheet) === '') {
                throw new GoogleSheetsException('Konfigurasi spreadsheet atau nama sheet stock belum lengkap. Hubungi pengelola aplikasi.');
            }

            $identifier = trim($identifier);
            if ($identifier === '') {
                throw new GoogleSheetsException('Identifier item stock tidak tersedia. Data tidak diperbarui.');
            }

            $token = $this->google->accessToken();
            $cache = Cache::store('file');
            $lockKey = 'appsheet:stock-write:'.$stockKind;

            return $cache->lock($lockKey, 30)->block(5, function () use (
                $cache,
                $definition,
                $spreadsheetId,
                $sheet,
                $token,
                $identifier,
                $submittedValues,
                $originalValues,
                $actor,
            ): array {
                $result = $this->updateFreshRow(
                    $definition,
                    $spreadsheetId,
                    $sheet,
                    $token,
                    $identifier,
                    $submittedValues,
                    $originalValues,
                    $actor,
                );

                if ($result['changed']) {
                    $cache->forget($definition['cache_key']);
                }

                return $result;
            });
        } catch (GoogleOAuthException) {
            throw new GoogleSheetsException('Koneksi Google perlu diperbarui. Silakan Hubungkan Ulang Google.', true);
        } catch (GoogleSheetsException $exception) {
            throw $exception;
        } catch (LockTimeoutException) {
            throw new GoogleSheetsException('Stock sedang diperbarui oleh pengguna lain. Silakan coba kembali sebentar lagi.');
        } catch (Throwable $exception) {
            // Jangan mencatat token, header Authorization, payload, atau respons Google.
            Log::warning('AppSheet Google Sheets stock update failed.', ['exception_type' => $exception::class]);

            throw new GoogleSheetsException('Stock belum dapat diperbarui. Silakan coba kembali.');
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, int|float|string>  $submittedValues
     * @param  array<string, int|float|string>  $originalValues
     * @return array{changed: bool, item_name: string, changes: array<string, array{old: float, new: float}>}
     */
    private function updateFreshRow(
        array $definition,
        string $spreadsheetId,
        string $sheet,
        #[SensitiveParameter] string $token,
        string $identifier,
        array $submittedValues,
        array $originalValues,
        string $actor,
    ): array {
        $values = $this->fetchValues($spreadsheetId, $sheet, $token);
        if ($values === []) {
            throw new GoogleSheetsException('Sheet stock belum memiliki header atau data item.');
        }

        $headers = is_array($values[0] ?? null) ? $values[0] : [];
        $positions = $this->headerPositions($headers, $definition, $sheet);
        $matches = [];

        foreach (array_slice($values, 1, null, true) as $rowIndex => $cells) {
            if (! is_array($cells)) {
                continue;
            }

            $rowIdentifier = $this->scalarString($cells[$positions[$definition['identifier_header']]] ?? null);
            if ($rowIdentifier === $identifier) {
                $matches[] = ['row_number' => $rowIndex + 1, 'cells' => $cells];
            }
        }

        if ($matches === []) {
            throw new GoogleSheetsException('Item stock dengan identifier tersebut tidak ditemukan. Data tidak diperbarui.');
        }
        if (count($matches) > 1) {
            throw new GoogleSheetsException('Identifier item stock ditemukan lebih dari satu kali. Data tidak diperbarui karena ambigu.');
        }

        $row = $matches[0]['cells'];
        $rowNumber = $matches[0]['row_number'];
        $currentValues = [];
        $changes = [];

        foreach ($definition['editable_fields'] as $requestField => $field) {
            $current = ConsumableData::number($row[$positions[$field['header']]] ?? null);
            $submitted = ConsumableData::number($submittedValues[$requestField] ?? null);
            $original = ConsumableData::number($originalValues[$requestField] ?? null);
            if ($current === null || $submitted === null || $original === null) {
                throw new GoogleSheetsException('Nilai stock pada Google Sheet tidak valid. Data tidak diperbarui.');
            }
            if (! $this->numbersEqual($current, $original)) {
                throw new GoogleSheetsException(sprintf(
                    'Stock telah berubah sejak halaman ini dibuka. Nilai terbaru %s adalah %s. Silakan periksa kembali sebelum melakukan perubahan.',
                    $field['label'],
                    StockData::displayQuantity($current),
                ));
            }

            $currentValues[$requestField] = $current;
            if (! $this->numbersEqual($current, $submitted)) {
                $changes[$requestField] = ['old' => $current, 'new' => $submitted];
            }
        }

        $itemName = $this->itemName($row, $positions, $definition, $identifier);
        if ($changes === []) {
            return ['changed' => false, 'item_name' => $itemName, 'changes' => []];
        }

        $updates = [];
        if ($definition['adjustment']) {
            $currentStockIn = ConsumableData::number($row[$positions['STOCK IN']] ?? null);
            $currentStockOut = ConsumableData::number($row[$positions['STOCK OUT']] ?? null);
            if ($currentStockIn === null || $currentStockOut === null) {
                throw new GoogleSheetsException('Nilai STOCK IN atau STOCK OUT pada Google Sheet tidak valid. Data tidak diperbarui.');
            }

            $newSpareStock = $changes['spare_stock']['new'];
            $delta = $newSpareStock - $currentValues['spare_stock'];
            if ($delta > 0) {
                $updates['STOCK IN'] = $currentStockIn + $delta;
            } elseif ($delta < 0) {
                $updates['STOCK OUT'] = $currentStockOut + abs($delta);
            }
            $updates['SPARE STOCK'] = $newSpareStock;
        } else {
            foreach ($changes as $requestField => $change) {
                $updates[$definition['editable_fields'][$requestField]['header']] = $change['new'];
            }
        }

        $updates[$definition['audit_by_header']] = trim($actor);
        $updates[$definition['audit_date_header']] = now()->format('Y-m-d H:i:s');
        $this->writeCells($spreadsheetId, $sheet, $rowNumber, $positions, $updates, $token);

        return ['changed' => true, 'item_name' => $itemName, 'changes' => $changes];
    }

    /**
     * @return array{input_type: string, item_name: string, quantity: float, unit: string, stock_after: float}
     */
    private function createFreshConsumableTransaction(
        string $spreadsheetId,
        string $stockSheet,
        string $historySheet,
        #[SensitiveParameter] string $token,
        string $identifier,
        string $inputType,
        float $quantity,
        ?string $usagePurpose,
        ?string $requestType,
        string $actor,
    ): array {
        $stockValues = $this->fetchValues($spreadsheetId, $stockSheet, $token);
        $historyValues = $this->fetchValues($spreadsheetId, $historySheet, $token);
        if ($stockValues === [] || $historyValues === []) {
            throw new GoogleSheetsException('Sheet History atau Stock Consumable belum memiliki header.');
        }

        $definition = StockSheetMap::definition(StockSheetMap::CONSUMABLE_BMS);
        $stockHeaders = is_array($stockValues[0] ?? null) ? $stockValues[0] : [];
        $stockPositions = $this->headerPositions($stockHeaders, $definition, $stockSheet);
        $stockPositions += $this->exactHeaderPositions($stockHeaders, ['CATEGORY'], $stockSheet);
        $historyHeaders = is_array($historyValues[0] ?? null) ? $historyValues[0] : [];
        $historyPositions = $this->exactHeaderPositions(
            $historyHeaders,
            GoogleSheetsReader::HISTORY_HEADERS,
            $historySheet,
        );

        $matches = [];
        foreach (array_slice($stockValues, 1, null, true) as $rowIndex => $cells) {
            if (! is_array($cells)) {
                continue;
            }
            if ($this->scalarString($cells[$stockPositions['UID']] ?? null) === $identifier) {
                $matches[] = ['row_number' => $rowIndex + 1, 'cells' => $cells];
            }
        }
        if ($matches === []) {
            throw new GoogleSheetsException('Barang tidak ditemukan pada data stock terbaru.');
        }
        if (count($matches) > 1) {
            throw new GoogleSheetsException('Data UID barang tidak unik sehingga transaksi tidak dapat diproses.');
        }

        $rowNumber = $matches[0]['row_number'];
        $row = $matches[0]['cells'];
        $stockIn = ConsumableData::number($row[$stockPositions['STOCK IN']] ?? null);
        $stockOut = ConsumableData::number($row[$stockPositions['STOCK OUT']] ?? null);
        $spareStock = ConsumableData::number($row[$stockPositions['SPARE STOCK']] ?? null);
        $category = $this->scalarString($row[$stockPositions['CATEGORY']] ?? null);
        if (mb_strtoupper($category) !== 'CONSUMABLE') {
            throw new GoogleSheetsException('Barang tidak ditemukan pada data Stock Consumable BMS terbaru.');
        }
        if ($stockIn === null || $stockOut === null || $spareStock === null) {
            throw new GoogleSheetsException('Nilai stock terbaru tidak valid sehingga transaksi dibatalkan.');
        }

        $formulaValues = $this->fetchValues($spreadsheetId, $stockSheet, $token, 'FORMULA');
        $formulaRow = is_array($formulaValues[$rowNumber - 1] ?? null) ? $formulaValues[$rowNumber - 1] : [];
        foreach (['STOCK IN', 'STOCK OUT', 'SPARE STOCK', 'INPUT. BY', 'INPUT DATE'] as $header) {
            $formula = $formulaRow[$stockPositions[$header]] ?? null;
            if (is_string($formula) && str_starts_with(trim($formula), '=')) {
                throw new GoogleSheetsException('Kolom '.$header.' menggunakan formula Google Sheets sehingga transaksi tidak dapat menimpanya.');
            }
        }

        $unit = $this->scalarString($row[$stockPositions['STN']] ?? null) ?: 'unit';
        if ($inputType === self::TRANSACTION_STOCK_OUT && $quantity > $spareStock) {
            throw new GoogleSheetsException(sprintf(
                'Stok tidak mencukupi. Stok tersedia %s %s, sedangkan jumlah yang diminta %s %s.',
                StockData::displayQuantity($spareStock),
                $unit,
                StockData::displayQuantity($quantity),
                $unit,
            ));
        }

        $usagePurpose = $inputType === self::TRANSACTION_STOCK_OUT ? trim((string) $usagePurpose) : '-';
        $requestType = $inputType === self::TRANSACTION_STOCK_OUT ? trim((string) $requestType) : '-';
        if ($inputType === self::TRANSACTION_STOCK_OUT) {
            $availableRequestTypes = collect(array_slice($historyValues, 1))
                ->map(fn (mixed $historyRow): string => is_array($historyRow)
                    ? $this->scalarString($historyRow[$historyPositions['JENIS PERMINTAAN']] ?? null)
                    : '')
                ->filter(fn (string $value): bool => $value !== '' && $value !== '-')
                ->unique()
                ->all();
            if ($usagePurpose === '') {
                throw new GoogleSheetsException('Tujuan penggunaan wajib diisi untuk STOCK OUT.');
            }
            if ($requestType === '' || ! in_array($requestType, $availableRequestTypes, true)) {
                throw new GoogleSheetsException('Jenis permintaan tidak tersedia pada data History Consumable terbaru.');
            }
        }

        $stockAfter = $inputType === self::TRANSACTION_STOCK_IN
            ? $spareStock + $quantity
            : $spareStock - $quantity;
        $stockUpdates = $inputType === self::TRANSACTION_STOCK_IN
            ? ['STOCK IN' => $stockIn + $quantity, 'SPARE STOCK' => $stockAfter]
            : ['STOCK OUT' => $stockOut + $quantity, 'SPARE STOCK' => $stockAfter];
        $timestamp = now()->format('Y-m-d H:i:s');
        $stockUpdates['INPUT. BY'] = trim($actor);
        $stockUpdates['INPUT DATE'] = $timestamp;

        $historyRow = [
            'INPUT DATE' => $timestamp,
            'UID' => $identifier,
            'DESC.' => $this->scalarString($row[$stockPositions['DESC.']] ?? null),
            'CATEGORY' => $category,
            'INPUT TYPE' => $inputType,
            'QTY' => $quantity,
            'TUJUAN PENGGUNAAN' => $usagePurpose,
            'JENIS PERMINTAAN' => $requestType,
            'INPUT BY' => trim($actor),
        ];
        $sheetIds = $this->fetchSheetIds($spreadsheetId, [$stockSheet, $historySheet], $token);
        $this->writeConsumableTransaction(
            $spreadsheetId,
            $sheetIds[$stockSheet],
            $sheetIds[$historySheet],
            $rowNumber,
            $stockPositions,
            $stockUpdates,
            $historyPositions,
            $historyRow,
            $token,
        );

        return [
            'input_type' => $inputType,
            'item_name' => $historyRow['DESC.'] ?: $identifier,
            'quantity' => $quantity,
            'unit' => $unit,
            'stock_after' => $stockAfter,
        ];
    }

    /** @return array<int, array<int, mixed>> */
    private function fetchValues(
        string $spreadsheetId,
        string $sheet,
        #[SensitiveParameter] string $token,
        string $valueRenderOption = 'UNFORMATTED_VALUE',
    ): array {
        $range = $this->quotedSheet($sheet);
        $response = Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->withoutRedirecting()
            ->get('https://sheets.googleapis.com/v4/spreadsheets/'.rawurlencode($spreadsheetId).'/values/'.rawurlencode($range), [
                'majorDimension' => 'ROWS',
                'valueRenderOption' => $valueRenderOption,
                'dateTimeRenderOption' => 'SERIAL_NUMBER',
            ]);
        $this->ensureReadSuccessful($response, $sheet);

        $values = $response->json('values');
        if (! is_array($values)) {
            throw new GoogleSheetsException('Respons data Google Sheets tidak valid. Stock belum diperbarui.');
        }

        return $values;
    }

    /**
     * @param  list<string>  $sheetNames
     * @return array<string, int>
     */
    private function fetchSheetIds(
        string $spreadsheetId,
        array $sheetNames,
        #[SensitiveParameter] string $token,
    ): array {
        $response = Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->withoutRedirecting()
            ->get('https://sheets.googleapis.com/v4/spreadsheets/'.rawurlencode($spreadsheetId), [
                'fields' => 'sheets.properties(sheetId,title)',
            ]);
        $this->ensureTransactionResponseSuccessful($response);

        $ids = [];
        foreach ($response->json('sheets', []) as $sheet) {
            $title = $sheet['properties']['title'] ?? null;
            $sheetId = $sheet['properties']['sheetId'] ?? null;
            if (is_string($title) && is_int($sheetId) && in_array($title, $sheetNames, true)) {
                $ids[$title] = $sheetId;
            }
        }
        foreach ($sheetNames as $sheetName) {
            if (! array_key_exists($sheetName, $ids)) {
                throw new GoogleSheetsException('Sheet "'.$sheetName.'" tidak ditemukan atau tidak dapat diakses.');
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $stockPositions
     * @param  array<string, float|string>  $stockUpdates
     * @param  array<string, int>  $historyPositions
     * @param  array<string, float|string>  $historyRow
     */
    private function writeConsumableTransaction(
        string $spreadsheetId,
        int $stockSheetId,
        int $historySheetId,
        int $stockRowNumber,
        array $stockPositions,
        array $stockUpdates,
        array $historyPositions,
        array $historyRow,
        #[SensitiveParameter] string $token,
    ): void {
        $requests = [];
        foreach ($stockUpdates as $header => $value) {
            $columnIndex = $stockPositions[$header];
            $requests[] = ['updateCells' => [
                'range' => [
                    'sheetId' => $stockSheetId,
                    'startRowIndex' => $stockRowNumber - 1,
                    'endRowIndex' => $stockRowNumber,
                    'startColumnIndex' => $columnIndex,
                    'endColumnIndex' => $columnIndex + 1,
                ],
                'rows' => [['values' => [['userEnteredValue' => $this->userEnteredValue($value)]]]],
                'fields' => 'userEnteredValue',
            ]];
        }

        $historyCells = array_fill(0, max($historyPositions) + 1, []);
        foreach ($historyRow as $header => $value) {
            $historyCells[$historyPositions[$header]] = ['userEnteredValue' => $this->userEnteredValue($value)];
        }
        $requests[] = ['appendCells' => [
            'sheetId' => $historySheetId,
            'rows' => [['values' => $historyCells]],
            'fields' => 'userEnteredValue',
        ]];

        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->withoutRedirecting()
            ->post('https://sheets.googleapis.com/v4/spreadsheets/'.rawurlencode($spreadsheetId).':batchUpdate', [
                'includeSpreadsheetInResponse' => false,
                'requests' => $requests,
            ]);
        $this->ensureTransactionResponseSuccessful($response);
    }

    /** @return array{numberValue: float}|array{stringValue: string} */
    private function userEnteredValue(float|string $value): array
    {
        return is_float($value)
            ? ['numberValue' => $value]
            : ['stringValue' => $value];
    }

    /**
     * @param  array<string, int>  $positions
     * @param  array<string, float|string>  $updates
     */
    private function writeCells(
        string $spreadsheetId,
        string $sheet,
        int $rowNumber,
        array $positions,
        array $updates,
        #[SensitiveParameter] string $token,
    ): void {
        $data = [];
        foreach ($updates as $header => $value) {
            $column = $this->columnName($positions[$header] + 1);
            $data[] = [
                'range' => $this->quotedSheet($sheet).'!'.$column.$rowNumber,
                'majorDimension' => 'ROWS',
                'values' => [[$value]],
            ];
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->withoutRedirecting()
            ->post('https://sheets.googleapis.com/v4/spreadsheets/'.rawurlencode($spreadsheetId).'/values:batchUpdate', [
                'valueInputOption' => 'RAW',
                'includeValuesInResponse' => false,
                'data' => $data,
            ]);

        $this->ensureWriteSuccessful($response);
    }

    /**
     * @param  array<int, mixed>  $headers
     * @param  array<string, mixed>  $definition
     * @return array<string, int>
     */
    private function headerPositions(array $headers, array $definition, string $sheet): array
    {
        $requiredHeaders = [
            $definition['identifier_header'],
            ...$definition['name_headers'],
            $definition['unit_header'],
            $definition['audit_by_header'],
            $definition['audit_date_header'],
            ...array_column($definition['editable_fields'], 'header'),
            ...$definition['additional_headers'],
        ];
        $acceptedHeaders = array_map(
            fn (string $header): string => $this->normalizeHeader($header),
            [...$requiredHeaders, ...$definition['identifier_aliases']],
        );

        $available = [];
        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeHeader($header);
            if ($normalized === '' || ! in_array($normalized, $acceptedHeaders, true)) {
                continue;
            }
            if (array_key_exists($normalized, $available)) {
                throw new GoogleSheetsException('Header "'.$normalized.'" berulang pada sheet "'.$sheet.'". Data tidak diperbarui.');
            }
            $available[$normalized] = $index;
        }

        $positions = [];
        foreach (array_unique($requiredHeaders) as $header) {
            $candidates = $header === $definition['identifier_header']
                ? [$header, ...$definition['identifier_aliases']]
                : [$header];

            foreach ($candidates as $candidate) {
                $normalized = $this->normalizeHeader($candidate);
                if (array_key_exists($normalized, $available)) {
                    $positions[$header] = $available[$normalized];

                    continue 2;
                }
            }

            throw new GoogleSheetsException('Header "'.$header.'" tidak ditemukan pada sheet stock. Data tidak diperbarui.');
        }

        return $positions;
    }

    /**
     * @param  array<int, mixed>  $headers
     * @param  list<string>  $requiredHeaders
     * @return array<string, int>
     */
    private function exactHeaderPositions(array $headers, array $requiredHeaders, string $sheet): array
    {
        $acceptedHeaders = array_map($this->normalizeHeader(...), $requiredHeaders);
        $available = [];
        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeHeader($header);
            if ($normalized === '' || ! in_array($normalized, $acceptedHeaders, true)) {
                continue;
            }
            if (array_key_exists($normalized, $available)) {
                throw new GoogleSheetsException('Header "'.$normalized.'" berulang pada sheet "'.$sheet.'". Data tidak diperbarui.');
            }
            $available[$normalized] = $index;
        }

        $positions = [];
        foreach ($requiredHeaders as $header) {
            $normalized = $this->normalizeHeader($header);
            if (! array_key_exists($normalized, $available)) {
                throw new GoogleSheetsException('Header "'.$header.'" tidak ditemukan pada sheet "'.$sheet.'".');
            }
            $positions[$header] = $available[$normalized];
        }

        return $positions;
    }

    /** @param array<string, mixed> $definition */
    private function itemName(array $row, array $positions, array $definition, string $fallback): string
    {
        foreach ($definition['name_headers'] as $header) {
            $name = $this->scalarString($row[$positions[$header]] ?? null);
            if ($name !== '' && $name !== '-') {
                return $name;
            }
        }

        return $fallback;
    }

    private function ensureReadSuccessful(Response $response, string $sheet): void
    {
        if ($response->status() === 401) {
            throw new GoogleSheetsException('Koneksi Google perlu diperbarui. Silakan Hubungkan Ulang Google.', true);
        }
        if ($response->status() === 403) {
            throw new GoogleSheetsException('Koneksi Google belum memiliki izin edit Google Sheets. Silakan Hubungkan Ulang Google.', true);
        }
        if (in_array($response->status(), [400, 404], true)) {
            throw new GoogleSheetsException('Sheet stock "'.$sheet.'" tidak ditemukan atau tidak dapat diakses.');
        }
        if (! $response->successful()) {
            throw new GoogleSheetsException('Stock belum dapat diperbarui. Silakan coba kembali.');
        }
    }

    private function ensureWriteSuccessful(Response $response): void
    {
        if ($response->status() === 401) {
            throw new GoogleSheetsException('Koneksi Google perlu diperbarui. Silakan Hubungkan Ulang Google.', true);
        }
        if ($response->status() === 403) {
            throw new GoogleSheetsException('Koneksi Google belum memiliki izin edit Google Sheets. Silakan Hubungkan Ulang Google.', true);
        }
        if (! $response->successful()) {
            throw new GoogleSheetsException('Stock belum dapat diperbarui. Silakan coba kembali.');
        }
    }

    private function ensureTransactionResponseSuccessful(Response $response): void
    {
        if ($response->status() === 401) {
            throw new GoogleSheetsException('Koneksi Google perlu diperbarui. Silakan Hubungkan Ulang Google.', true);
        }
        if ($response->status() === 403) {
            throw new GoogleSheetsException('Koneksi Google belum memiliki izin edit Google Sheets. Silakan Hubungkan Ulang Google.', true);
        }
        if (in_array($response->status(), [400, 404], true)) {
            throw new GoogleSheetsException('Sheet History atau Stock Consumable tidak ditemukan atau tidak dapat diakses.');
        }
        if (! $response->successful()) {
            throw new GoogleSheetsException('Transaksi belum dapat disimpan ke Google Sheets. Silakan coba kembali.');
        }
    }

    private function quotedSheet(string $sheet): string
    {
        return "'".str_replace("'", "''", $sheet)."'";
    }

    private function normalizeHeader(mixed $header): string
    {
        if (! is_string($header)) {
            return '';
        }

        $header = str_replace("\u{00A0}", ' ', trim($header));

        return mb_strtoupper(preg_replace('/\s+/u', ' ', $header) ?? $header);
    }

    private function scalarString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function numbersEqual(float $left, float $right): bool
    {
        $scale = max(1.0, abs($left), abs($right));

        return abs($left - $right) <= $scale * 1.0E-12;
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }
}
