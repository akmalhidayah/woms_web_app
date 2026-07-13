<?php

namespace App\Support;

class BastApprovalFlow
{
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

        $termin = $terminType === 'termin_2' ? 'T2' : 'T1';
        $bucket = $threshold === 'over_250' ? 'OVER250' : 'UNDER250';

        return "BAST-{$termin}-{$bucket}";
    }

    /**
     * @return list<string>
     */
    public static function resolveApprovalFlow(string $threshold): array
    {
        return self::flowMatrix()[$threshold] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function flowMatrix(): array
    {
        return [
            'under_250' => [
                'Manager PKM',
                'Manager Peminta',
                'Manager Pengendali',
                'GM Pengendali',
            ],
            'over_250' => [
                'Manager PKM',
                'Manager Peminta',
                'Manager Pengendali',
                'GM Pengendali',
                'DIROPS',
            ],
        ];
    }
}
