<?php

namespace App\Support\AppSheet;

use DateTimeImmutable;
use DateTimeZone;

class ConsumableData
{
    public static function number(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? (float) $value : null;
        }
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $number = trim($value);
        // Angka asli Sheets sudah numerik; dukung juga angka yang disimpan sebagai teks.
        if (preg_match('/^[+-]?\d{1,3}(?:,\d{3})+(?:\.\d+)?$/', $number)) {
            $number = str_replace(',', '', $number);
        }

        return is_numeric($number) && is_finite((float) $number) ? (float) $number : null;
    }

    public static function stockStatus(array $row): ?string
    {
        $consignment = self::quantity($row['QTY KONSINYASI'] ?? '');
        $nonConsignment = self::quantity($row['QTY NON KONSINYASI'] ?? '');
        if ($consignment === null || $nonConsignment === null) {
            return null;
        }

        $total = $consignment + $nonConsignment;
        if (! is_finite($total)) {
            return null;
        }
        if ($total <= 0) {
            return 'habis';
        }

        // Minimum kosong = 0: total positif tidak dikategorikan rendah.
        $minimum = self::quantity($row['MIN'] ?? '');
        if ($minimum === null) {
            // Angka tidak valid tidak boleh menghasilkan status stok yang menyesatkan.
            return null;
        }

        return $total <= $minimum ? 'rendah' : 'aman';
    }

    public static function date(mixed $value): ?DateTimeImmutable
    {
        $timezone = new DateTimeZone('UTC');
        if (is_int($value) || is_float($value)) {
            if (! is_finite((float) $value) || $value < 0 || $value > 2958465) {
                return null;
            }

            // Sheets serial = hari sejak 1899-12-30; pecahan menyimpan jam lokal sheet.
            return (new DateTimeImmutable('1899-12-30', $timezone))->modify('+'.(int) round($value * 86400).' seconds');
        }
        if (! is_string($value) || trim($value) === '' || str_contains($value, "\0")) {
            return null;
        }

        // Tanggal bertipe date memakai serial API. Tanggal teks lokal memakai hari/bulan/tahun.
        foreach (['Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s.uP', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d',
            'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'd-m-Y H:i:s', 'd-m-Y H:i', 'd-m-Y',
            'm/d/Y h:i:s A', 'm/d/Y h:i A'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, trim($value), $timezone);
            $errors = DateTimeImmutable::getLastErrors();
            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date;
            }
        }

        return null;
    }

    public static function displayDate(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        $date = self::date($value);

        if ($date === null) {
            return '';
        }

        return $date->format($date->format('H:i:s') === '00:00:00' ? 'd/m/Y' : 'd/m/Y H:i:s');
    }

    public static function matchesSearch(array $row, array $headers, string $search): bool
    {
        if ($search === '') {
            return true;
        }
        foreach ($headers as $header) {
            if (mb_stripos((string) ($row[$header] ?? ''), $search) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function quantity(mixed $value): ?float
    {
        return $value === null || (is_string($value) && trim($value) === '') ? 0.0 : self::number($value);
    }
}
