<?php

namespace App\Support\AppSheet;

class StockData
{
    public static function consumableGudang(array $row): array
    {
        return self::item($row, [
            'code' => 'NO MATERIAL', 'name' => 'CONSUMABLE', 'type' => 'JENIS CONSUMABLE',
            'description' => 'DESKRIPSI', 'unit' => 'STN', 'updated_by' => 'UPD. BY',
        ], 'UPD. DATE') + [
            'consignment' => $row['QTY KONSINYASI'] ?? null,
            'non_consignment' => $row['QTY NON KONSINYASI'] ?? null,
            'minimum' => $row['MIN'] ?? null,
        ];
    }

    public static function materialBms(array $row): array
    {
        return self::item($row, [
            'code' => 'UID', 'name' => 'JENIS MATERIAL', 'location' => 'LOC',
            'unit' => 'STN', 'updated_by' => 'UPD. BY',
        ], 'LAST UPDATE') + [
            'quantity' => $row['QTY'] ?? null,
            'status' => self::quantityStatus($row['QTY'] ?? null),
        ];
    }

    public static function materialGudang(array $row): array
    {
        return self::item($row, [
            'code' => 'NO. MATERIAL', 'name' => 'MATERIAL', 'type' => 'MRP TYPE',
            'description' => 'DESKRIPSI', 'location' => 'MATERIAL LOC',
            'unit' => 'STN', 'updated_by' => 'UPDATE BY',
        ], 'UPDATE DATE') + [
            'quantity' => $row['QTY'] ?? null,
            'capex' => $row['QTY CAPEX'] ?? null,
            'status' => self::quantityStatus($row['QTY'] ?? null),
        ];
    }

    public static function hasItem(array $row): bool
    {
        foreach (['code', 'name', 'description'] as $key) {
            if (! in_array($row[$key] ?? '', ['', '-'], true)) {
                return true;
            }
        }

        return false;
    }

    public static function quantityStatus(mixed $value): ?string
    {
        $quantity = ConsumableData::number($value);

        return $quantity === null ? null : ($quantity <= 0 ? 'habis' : 'tersedia');
    }

    public static function displayQuantity(mixed $value): string
    {
        // UNFORMATTED_VALUE mengembalikan angka asli, terlepas dari locale sheet.
        // Teks ambigu seperti "12.000" ditampilkan apa adanya, tanpa menebak separator.
        if (is_string($value)) {
            return trim($value) !== '' ? trim($value) : '-';
        }
        if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value)) {
            return '-';
        }

        $number = (string) json_encode($value, JSON_PRESERVE_ZERO_FRACTION);
        preg_match('/^[+-]?\d+(?:\.(\d+))?(?:[eE]([+-]?\d+))?$/', $number, $parts);
        $decimals = max(0, strlen(rtrim($parts[1] ?? '', '0')) - (int) ($parts[2] ?? 0));

        // Pertahankan presisi nilai numerik; jangan membulatkan quantity menjadi integer.
        return $decimals > 20 ? $number : number_format($value, $decimals, ',', '.');
    }

    private static function item(array $row, array $fields, string $dateHeader): array
    {
        $item = array_fill_keys(['code', 'name', 'type', 'description', 'location', 'unit', 'updated_by'], '');
        foreach ($fields as $key => $header) {
            $value = $row[$header] ?? '';
            $item[$key] = is_scalar($value) ? trim((string) $value) : '';
        }

        $rawDate = $row[$dateHeader] ?? '';
        $date = ConsumableData::date($rawDate);

        return $item + [
            'date_timestamp' => $date?->getTimestamp(),
            'date_display' => $date?->format('d/m/Y') ?? ConsumableData::displayDate($rawDate),
            'time_display' => $date !== null && $date->format('H:i:s') !== '00:00:00'
                ? $date->format('H:i:s') : null,
        ];
    }
}
