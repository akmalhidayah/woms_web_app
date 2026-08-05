<?php

namespace App\Support;

class BastApprovalFlow
{
    /** @var array<string, list<string>> */
    private const TERMIN_ONE_MATRIX = [
        'under_250' => [
            'Manager PKM',
            'Manager Peminta',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
        ],
        'over_250' => [
            'Manager PKM',
            'Manager Peminta',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
            'DIROPS',
        ],
    ];

    /** @var array<string, list<string>> */
    private const TERMIN_TWO_MATRIX = [
        'under_250' => [
            'Manager PKM',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
        ],
        'over_250' => [
            'Manager PKM',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
            'DIROPS',
        ],
    ];

    /**
     * @return array<string, string>
     */
    public static function thresholdOptions(): array
    {
        return [
            'under_250' => '≤ Rp250 juta',
            'over_250' => '> Rp250 juta',
        ];
    }

    public static function resolveApprovalCase(string $terminType, string $threshold): ?string
    {
        if (! array_key_exists($threshold, self::thresholdOptions())) {
            return null;
        }

        $termin = self::normalizeTerminType($terminType) === 'termin_2' ? 'T2' : 'T1';
        $bucket = $threshold === 'over_250' ? 'OVER250' : 'UNDER250';

        return "BAST-{$termin}-{$bucket}";
    }

    /**
     * @return list<string>
     */
    public static function resolveApprovalFlow(string $threshold, string $terminType = 'termin_1'): array
    {
        return self::flowMatrix($terminType)[$threshold] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function flowMatrix(string $terminType = 'termin_1'): array
    {
        return self::normalizeTerminType($terminType) === 'termin_2'
            ? self::TERMIN_TWO_MATRIX
            : self::TERMIN_ONE_MATRIX;
    }

    private static function normalizeTerminType(string $terminType): string
    {
        return in_array(strtolower(trim($terminType)), ['termin_2', 'termin-2', '2'], true)
            ? 'termin_2'
            : 'termin_1';
    }
}
