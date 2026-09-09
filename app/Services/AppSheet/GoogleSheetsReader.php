<?php

namespace App\Services\AppSheet;

use App\Exceptions\AppSheet\GoogleOAuthException;
use App\Exceptions\AppSheet\GoogleSheetsException;
use Illuminate\Contracts\Cache\LockTimeoutException;
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
        'NO MATERIAL', 'JENIS CONSUMABLE', 'CONSUMABLE', 'DESKRIPSI',
        'QTY KONSINYASI', 'QTY NON KONSINYASI', 'UNIT', 'UPD. BY', 'UPD. DATE', 'MIN',
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

    private function read(string $sheetConfig, string $cacheKey, array $headers): array
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

            return $cache->lock($cacheKey.':lock', 35)->block(5, function () use ($cache, $cacheKey, $source, $spreadsheetId, $sheet, $token, $headers): array {
                $cached = $cache->get($cacheKey);
                if (is_array($cached) && ($cached['source'] ?? null) === $source) {
                    return $cached['rows'];
                }

                $rows = $this->mapRows($this->fetchValues($spreadsheetId, $sheet, $token), $headers, $sheet);
                if (! $cache->put($cacheKey, ['source' => $source, 'rows' => $rows], 30)) {
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
        $response = Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->withoutRedirecting()
            ->get('https://sheets.googleapis.com/v4/spreadsheets/'.rawurlencode($spreadsheetId).'/values/'.rawurlencode($range), [
                'majorDimension' => 'ROWS',
                'valueRenderOption' => 'UNFORMATTED_VALUE',
                'dateTimeRenderOption' => 'SERIAL_NUMBER',
            ]);

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

        $data = $response->json();
        if (! is_array($data) || ! isset($data['range']) || ! is_array($data['values'] ?? [])) {
            throw new GoogleSheetsException('Respons data Google Sheets tidak valid. Silakan coba kembali.');
        }

        return $data['values'] ?? [];
    }

    private function mapRows(array $values, array $requiredHeaders, string $sheet): array
    {
        if ($values === []) {
            return [];
        }

        $positions = [];
        foreach ((array) array_shift($values) as $index => $header) {
            $name = is_string($header) ? mb_strtoupper(trim($header)) : '';
            if ($name !== '' && in_array($name, $requiredHeaders, true)) {
                if (array_key_exists($name, $positions)) {
                    throw new GoogleSheetsException('Header "'.$name.'" berulang pada sheet "'.$sheet.'". Periksa baris pertama sheet.');
                }
                $positions[$name] = $index;
            }
        }

        $missing = array_diff($requiredHeaders, array_keys($positions));
        if ($missing !== []) {
            throw new GoogleSheetsException('Header sheet "'.$sheet.'" belum sesuai: '.implode(', ', $missing).'.');
        }

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
}
