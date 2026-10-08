<?php

namespace App\Support\AppSheet;

class DailyReportData
{
    /**
     * @return array{title: string, user: string, unit: string, parsed: bool}
     */
    public static function work(array $row): array
    {
        $description = self::inline($row['DESC. ORDER'] ?? '');
        if ($description !== '') {
            return [
                'title' => $description,
                'user' => '',
                'unit' => '',
                'parsed' => false,
            ];
        }

        $raw = self::multiline($row['INPUT NON ORDER'] ?? '');
        if ($raw === '') {
            return ['title' => '-', 'user' => '', 'unit' => '', 'parsed' => false];
        }

        $labels = 'JUDUL PEKERJAAN|NAMA USER|NAMA UNIT KERJA';
        preg_match_all(
            '/(?:\A|\R)\h*('.$labels.')\h*:\h*(.*?)(?=\R\h*(?:'.$labels.')\h*:|\z)/isu',
            $raw,
            $matches,
            PREG_SET_ORDER,
        );

        $fields = [];
        foreach ($matches as $match) {
            $label = mb_strtoupper(self::inline($match[1] ?? ''));
            $value = self::inline($match[2] ?? '');
            if ($label !== '' && $value !== '' && ! array_key_exists($label, $fields)) {
                $fields[$label] = $value;
            }
        }

        $title = $fields['JUDUL PEKERJAAN'] ?? '';
        if ($title === '') {
            return ['title' => $raw, 'user' => '', 'unit' => '', 'parsed' => false];
        }

        return [
            'title' => $title,
            'user' => $fields['NAMA USER'] ?? '',
            'unit' => $fields['NAMA UNIT KERJA'] ?? '',
            'parsed' => true,
        ];
    }

    public static function isReportRow(array $row): bool
    {
        return self::inline($row['ORDER'] ?? '') !== ''
            || self::inline($row['DESC. ORDER'] ?? '') !== ''
            || self::multiline($row['INPUT NON ORDER'] ?? '') !== '';
    }

    /**
     * @return list<string>
     */
    public static function picNames(mixed $value): array
    {
        $value = self::multiline($value);
        if ($value === '') {
            return [];
        }

        $names = preg_split('/\s*(?:,|;|\R)\s*/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $unique = [];
        foreach ($names as $name) {
            $cleanName = self::inline($name);
            $key = self::nameKey($cleanName);
            if ($key !== '' && ! array_key_exists($key, $unique)) {
                $unique[$key] = $cleanName;
            }
        }

        return array_values($unique);
    }

    public static function year(mixed $value): string
    {
        $year = self::inline($value);

        return preg_match('/\A(\d{4})(?:\.0+)?\z/', $year, $matches) ? $matches[1] : '';
    }

    public static function nameKey(mixed $value): string
    {
        return mb_strtolower(self::inline($value));
    }

    public static function inline(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        return preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
    }

    public static function multiline(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $lines = preg_split('/\R/u', trim((string) $value)) ?: [];
        $lines = array_map(
            fn (string $line): string => preg_replace('/\h+/u', ' ', trim($line)) ?? '',
            $lines,
        );

        return implode("\n", array_values(array_filter($lines, fn (string $line): bool => $line !== '')));
    }
}
