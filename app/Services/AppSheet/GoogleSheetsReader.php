<?php

namespace App\Services\AppSheet;

use App\Exceptions\AppSheet\GoogleOAuthException;
use App\Exceptions\AppSheet\GoogleSheetsException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SensitiveParameter;
use Throwable;

class GoogleSheetsReader
{
    public const HISTORY_HEADERS = [
        'INPUT DATE', 'UID', 'DESC.', 'CATEGORY', 'INPUT TYPE', 'QTY',
        'TUJUAN PENGGUNAAN', 'JENIS PERMINTAAN', 'INPUT BY',
    ];

    public const STOCK_HEADERS = [
        'UID', 'IMG', 'TYPE CATEGORY', 'DESC.', 'SIZE', 'STOCK IN', 'STOCK OUT',
        'SPARE STOCK', 'STN', 'CATEGORY', 'SUB CATEGORY', 'LOC', 'INPUT. BY', 'INPUT DATE',
    ];

    public const PROFILE_HEADERS = [
        'NAMA', 'REGU', 'SHIFT', 'JABATAN', 'IMG',
    ];

    public const DAILY_REPORT_HEADERS = [
        'ORDER', 'DESC. ORDER', 'ATTACHMENT', 'INPUT NON ORDER', 'PROGRESS PEKERJAAN',
        'POTO PEKERJAAN', 'PIC', 'INPUT BY', 'INPUT DATE', 'TAHUN',
    ];

    public function __construct(private readonly GoogleOAuthService $google) {}

    public function historyConsumable(): array
    {
        return $this->read('history_consumable_sheet', 'appsheet:history-consumable', self::HISTORY_HEADERS);
    }

    public function stockConsumable(): array
    {
        return $this->read('stock_consumable_sheet', 'appsheet:stock-consumable', self::STOCK_HEADERS);
    }

    public function requesterProfiles(): array
    {
        return $this->read('data_sheet', 'appsheet:requester-profiles', self::PROFILE_HEADERS, 300, true);
    }

    public function dailyReports(): array
    {
        return $this->read(
            'daily_report_sheet',
            'appsheet:daily-reports:v2',
            self::DAILY_REPORT_HEADERS,
            headerAliases: ['POTO PEKERJAAN' => ['FOTO PEKERJAAN']],
        );
    }

    private function read(
        string $sheetConfig,
        string $cacheKey,
        array $headers,
        int $cacheTtl = 30,
        bool $selectedColumnsOnly = false,
        array $headerAliases = [],
    ): array
    {
        try {
            $spreadsheetId = config('services.google.spreadsheet_id');
            $sheet = config('services.google.'.$sheetConfig);
            if (! is_string($spreadsheetId) || trim($spreadsheetId) === ''
                || ! is_string($sheet) || trim($sheet) === '') {
                throw new GoogleSheetsException('Konfigurasi spreadsheet atau nama sheet AppSheet belum lengkap. Hubungi pengelola aplikasi.');
            }

            // Service OAuth existing menangani expiry/refresh; token tidak masuk cache data.
            $token = $this->google->accessToken();
            $source = hash('sha256', $spreadsheetId."\0".$sheet."\0".$token);
            $cache = Cache::store('file');
            $cached = $cache->get($cacheKey);
            if (is_array($cached) && ($cached['source'] ?? null) === $source) {
                return $cached['rows'];
            }

            return $cache->lock($cacheKey.':lock', 35)->block(5, function () use ($cache, $cacheKey, $source, $spreadsheetId, $sheet, $token, $headers, $cacheTtl, $selectedColumnsOnly, $headerAliases): array {
                $cached = $cache->get($cacheKey);
                if (is_array($cached) && ($cached['source'] ?? null) === $source) {
                    return $cached['rows'];
                }

                $rows = $selectedColumnsOnly
                    ? $this->fetchSelectedColumns($spreadsheetId, $sheet, $token, $headers, $headerAliases)
                    : $this->mapRows($this->fetchValues($spreadsheetId, $sheet, $token), $headers, $sheet, $headerAliases);
                if (! $cache->put($cacheKey, ['source' => $source, 'rows' => $rows], $cacheTtl)) {
                    throw new GoogleSheetsException('Cache AppSheet belum dapat disimpan. Silakan coba kembali.');
                }

                return $rows;
            });
        } catch (GoogleOAuthException) {
            throw new GoogleSheetsException('Koneksi Google perlu diperbarui.', true);
        } catch (GoogleSheetsException $exception) {
            throw $exception;
        } catch (LockTimeoutException) {
            throw new GoogleSheetsException('Data AppSheet sedang dimuat. Silakan coba kembali sebentar lagi.');
        } catch (Throwable $exception) {
            // Jangan menyertakan request, header Authorization, body respons, atau trace.
            Log::warning('AppSheet Google Sheets read failed.', ['exception_type' => $exception::class]);

            throw new GoogleSheetsException('Data Google Sheets sementara belum dapat dimuat. Silakan coba kembali.');
        }
    }

    private function fetchValues(string $spreadsheetId, string $sheet, #[SensitiveParameter] string $token): array
    {
        // Nama sheet berpetik membaca seluruh sheet tanpa batas huruf kolom/baris.
        $range = "'".str_replace("'", "''", $sheet)."'";
        $response = $this->sheetsRequest($spreadsheetId, '/values/'.rawurlencode($range), $token, [
            'majorDimension' => 'ROWS',
            'valueRenderOption' => 'UNFORMATTED_VALUE',
            'dateTimeRenderOption' => 'SERIAL_NUMBER',
        ]);

        return $this->valuesFromResponse($response, $sheet);
    }

    private function fetchSelectedColumns(
        string $spreadsheetId,
        string $sheet,
        #[SensitiveParameter] string $token,
        array $requiredHeaders,
        array $headerAliases = [],
    ): array
    {
        $quotedSheet = "'".str_replace("'", "''", $sheet)."'";
        $headerResponse = $this->sheetsRequest($spreadsheetId, '/values/'.rawurlencode($quotedSheet.'!1:1'), $token, [
            'majorDimension' => 'ROWS',
            'valueRenderOption' => 'UNFORMATTED_VALUE',
        ]);
        $headerRows = $this->valuesFromResponse($headerResponse, $sheet);
        $positions = $this->headerPositions($headerRows[0] ?? [], $requiredHeaders, $sheet, $headerAliases);
        $ranges = [];
        foreach ($requiredHeaders as $header) {
            $column = $this->columnName($positions[$header] + 1);
            $ranges[] = $quotedSheet.'!'.$column.'2:'.$column;
        }

        $query = implode('&', array_map(fn (string $range): string => 'ranges='.rawurlencode($range), $ranges));
        $query .= '&majorDimension=ROWS&valueRenderOption=UNFORMATTED_VALUE';
        $response = Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->withoutRedirecting()
            ->get('https://sheets.googleapis.com/v4/spreadsheets/'.rawurlencode($spreadsheetId).'/values:batchGet?'.$query);
        $this->ensureSuccessfulResponse($response, $sheet);

        $valueRanges = $response->json('valueRanges');
        if (! is_array($valueRanges)) {
            throw new GoogleSheetsException('Respons data Google Sheets tidak valid. Silakan coba kembali.');
        }

        $rows = [];
        foreach ($requiredHeaders as $columnIndex => $header) {
            $values = $valueRanges[$columnIndex]['values'] ?? [];
            if (! is_array($values)) {
                throw new GoogleSheetsException('Respons data Google Sheets tidak valid. Silakan coba kembali.');
            }
            foreach ($values as $rowIndex => $cells) {
                $cell = is_array($cells) ? ($cells[0] ?? '') : '';
                $rows[$rowIndex][$header] = is_scalar($cell) ? $cell : '';
            }
        }

        $mapped = [];
        $rowCount = $rows === [] ? 0 : max(array_keys($rows)) + 1;
        for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++) {
            $row = [];
            foreach ($requiredHeaders as $header) {
                $row[$header] = $rows[$rowIndex][$header] ?? '';
            }
            if (collect($row)->contains(fn ($cell) => is_scalar($cell) && (! is_string($cell) || trim($cell) !== ''))) {
                $mapped[] = $row;
            }
        }

        return $mapped;
    }

    private function sheetsRequest(string $spreadsheetId, string $path, #[SensitiveParameter] string $token, array $query): Response
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->withoutRedirecting()
            ->get('https://sheets.googleapis.com/v4/spreadsheets/'.rawurlencode($spreadsheetId).$path, $query);

        return $response;
    }

    private function valuesFromResponse(Response $response, string $sheet): array
    {
        $this->ensureSuccessfulResponse($response, $sheet);

        $data = $response->json();
        if (! is_array($data) || ! isset($data['range']) || ! is_array($data['values'] ?? [])) {
            throw new GoogleSheetsException('Respons data Google Sheets tidak valid. Silakan coba kembali.');
        }

        return $data['values'] ?? [];
    }

    private function ensureSuccessfulResponse(Response $response, string $sheet): void
    {

        // Pesan API mentah tidak pernah diteruskan ke halaman atau log.
        if ($response->status() === 401) {
            throw new GoogleSheetsException('Koneksi Google perlu diperbarui.', true);
        }
        if ($response->status() === 400) {
            throw new GoogleSheetsException('Sheet "'.$sheet.'" tidak dapat dibaca. Pastikan nama sheet tersedia dan sesuai konfigurasi.');
        }
        if ($response->status() === 404) {
            throw new GoogleSheetsException('Spreadsheet untuk sheet "'.$sheet.'" tidak ditemukan atau tidak dapat diakses.');
        }
        if ($response->status() === 403) {
            throw new GoogleSheetsException('Akses ke sheet "'.$sheet.'" ditolak. Periksa izin akun Google dan aktivasi Google Sheets API.');
        }
        if (! $response->successful()) {
            throw new GoogleSheetsException('Data Google Sheets sementara belum dapat dimuat. Silakan coba kembali.');
        }
    }

    private function mapRows(array $values, array $requiredHeaders, string $sheet, array $headerAliases = []): array
    {
        if ($values === []) {
            return [];
        }

        $positions = $this->headerPositions((array) array_shift($values), $requiredHeaders, $sheet, $headerAliases);

        $rows = [];
        foreach ($values as $cells) {
            if (! is_array($cells) || ! collect($cells)->contains(fn ($cell) => is_scalar($cell) && (! is_string($cell) || trim($cell) !== ''))) {
                continue;
            }

            $row = [];
            foreach ($positions as $header => $index) {
                $cell = $cells[$index] ?? '';
                $row[$header] = is_scalar($cell) ? $cell : '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function headerPositions(array $headers, array $requiredHeaders, string $sheet, array $headerAliases = []): array
    {
        $acceptedHeaders = collect($requiredHeaders)
            ->flatMap(fn (string $header): array => [$header, ...($headerAliases[$header] ?? [])])
            ->map(fn (string $header): string => mb_strtoupper(trim($header)))
            ->all();
        $available = [];
        foreach ($headers as $index => $header) {
            $name = is_string($header) ? mb_strtoupper(trim($header)) : '';
            if ($name === '' || ! in_array($name, $acceptedHeaders, true)) {
                continue;
            }
            if (array_key_exists($name, $available)) {
                throw new GoogleSheetsException('Header "'.$name.'" berulang pada sheet "'.$sheet.'". Periksa baris pertama sheet.');
            }
            $available[$name] = $index;
        }

        $positions = [];
        $missing = [];
        foreach ($requiredHeaders as $requiredHeader) {
            $candidates = [$requiredHeader, ...($headerAliases[$requiredHeader] ?? [])];
            foreach ($candidates as $candidate) {
                $candidate = mb_strtoupper(trim((string) $candidate));
                if (array_key_exists($candidate, $available)) {
                    $positions[$requiredHeader] = $available[$candidate];
                    continue 2;
                }
            }
            $missing[] = $requiredHeader;
        }

        if ($missing !== []) {
            throw new GoogleSheetsException('Header sheet "'.$sheet.'" belum sesuai: '.implode(', ', $missing).'.');
        }

        return $positions;
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
