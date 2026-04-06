<?php

namespace App\Support;

class HppApprovalFlow
{
    public const THRESHOLD = 250_000_000;

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
        return [
            'Dalam' => 'Dalam (T.23,4,5, Pelabuhan BKS & Packing Plant)',
            'Luar' => 'Luar (BTG&CUS)',
            'Workshop' => 'Workshop',
        ];
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
        if ($kategoriPekerjaan === '' || $areaPekerjaan === '' || ! array_key_exists($nilaiBucket, static::bucketOptions())) {
            return null;
        }

        $prefix = $kategoriPekerjaan === 'Fabrikasi' ? 'FAB' : 'KONS';
        $area = strtoupper($areaPekerjaan);
        $bucket = $nilaiBucket === 'over' ? 'OVER250' : 'UNDER250';

        return "{$prefix}-{$area}-{$bucket}";
    }

    /**
     * @return list<string>
     */
    public static function resolveApprovalFlow(string $kategoriPekerjaan, string $areaPekerjaan, string $nilaiBucket): array
    {
        if ($kategoriPekerjaan === '' || $areaPekerjaan === '' || ! array_key_exists($nilaiBucket, static::bucketOptions())) {
            return [];
        }

        $flows = static::flowMatrix();

        return $flows[$kategoriPekerjaan][$areaPekerjaan][$nilaiBucket] ?? [];
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
