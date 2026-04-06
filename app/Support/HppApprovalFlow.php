<?php

namespace App\Support;

class HppApprovalFlow
{
    public const THRESHOLD = 250_000_000;

    /**
     * @return array<string, string>
     */
    public static function canonicalAreaLabels(): array
    {
        return [
            'Dalam' => 'Dalam (T.23,4,5, Pelabuhan BKS & Packing Plant)',
            'Luar' => 'Luar (BTG&CUS)',
            'Workshop' => 'Workshop',
        ];
    }

    /**
     * @return list<string>
     */
    public static function kategoriOptions(): array
    {
        return ['Fabrikasi', 'Konstruksi'];
    }

    /**
     * @return array<string, string>
     */
    public static function areaOptions(): array
    {
        return collect(static::canonicalAreaLabels())
            ->mapWithKeys(fn (string $label): array => [$label => $label])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function areaKeysByLabel(): array
    {
        return collect(static::canonicalAreaLabels())
            ->mapWithKeys(fn (string $label, string $key): array => [$label => $key])
            ->all();
    }

    public static function normalizeAreaKey(string $areaPekerjaan): string
    {
        $normalized = trim($areaPekerjaan);

        if ($normalized === '') {
            return '';
        }

        $labels = static::canonicalAreaLabels();
        $keysByLabel = static::areaKeysByLabel();

        if (array_key_exists($normalized, $labels)) {
            return $normalized;
        }

        return $keysByLabel[$normalized] ?? $normalized;
    }

    public static function displayArea(string $areaPekerjaan): string
    {
        $key = static::normalizeAreaKey($areaPekerjaan);

        return static::canonicalAreaLabels()[$key] ?? trim($areaPekerjaan);
    }

    /**
     * @return array<string, string>
     */
    public static function bucketOptions(): array
    {
        return [
            'under' => '<= 250 JT',
            'over' => '> 250 JT',
        ];
    }

    public static function resolvePreviewCase(string $kategoriPekerjaan, string $areaPekerjaan, string $nilaiBucket): ?string
    {
        $areaKey = static::normalizeAreaKey($areaPekerjaan);

        if ($kategoriPekerjaan === '' || $areaKey === '' || ! array_key_exists($nilaiBucket, static::bucketOptions())) {
            return null;
        }

        $prefix = $kategoriPekerjaan === 'Fabrikasi' ? 'FAB' : 'KONS';
        $area = strtoupper($areaKey);
        $bucket = $nilaiBucket === 'over' ? 'OVER250' : 'UNDER250';

        return "{$prefix}-{$area}-{$bucket}";
    }

    /**
     * @return list<string>
     */
    public static function resolveApprovalFlow(string $kategoriPekerjaan, string $areaPekerjaan, string $nilaiBucket): array
    {
        $areaKey = static::normalizeAreaKey($areaPekerjaan);

        if ($kategoriPekerjaan === '' || $areaKey === '' || ! array_key_exists($nilaiBucket, static::bucketOptions())) {
            return [];
        }

        $flows = static::flowMatrix();

        return $flows[$kategoriPekerjaan][$areaKey][$nilaiBucket] ?? [];
    }

    /**
     * @return array<string, array<string, array<string, list<string>>>>
     */
    public static function flowMatrix(): array
    {
        return [
            'Fabrikasi' => [
                'Dalam' => [
                    'under' => [
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                    ],
                    'over' => [
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                        'DIROPS',
                    ],
                ],
                'Workshop' => [
                    'under' => [
                        'Manager',
                        'SM',
                        'GM',
                    ],
                    'over' => [
                        'Manager',
                        'SM',
                        'GM',
                        'DIROPS',
                    ],
                ],
                'Luar' => [
                    'under' => [
                        'Planner Control',
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                    ],
                    'over' => [
                        'Planner Control',
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                        'DIROPS',
                    ],
                ],
            ],
            'Konstruksi' => [
                'Dalam' => [
                    'under' => [
                        'Manager Counter Part',
                        'SM Counter Part',
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                    ],
                    'over' => [
                        'Manager Counter Part',
                        'SM Counter Part',
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                        'DIROPS',
                    ],
                ],
                'Luar' => [
                    'under' => [
                        'Manager Counter Part',
                        'SM Counter Part',
                        'Planner Control',
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                    ],
                    'over' => [
                        'Manager Counter Part',
                        'SM Counter Part',
                        'Planner Control',
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                        'DIROPS',
                    ],
                ],
            ],
        ];
    }
}
