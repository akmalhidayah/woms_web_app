<?php

declare(strict_types=1);

namespace App\Support;

final class BastDisplayLabel
{
    public static function isWithoutWarranty(?int $garansiMonths): bool
    {
        return $garansiMonths === 0;
    }

    public static function stageLabel(string $terminType, ?int $garansiMonths): string
    {
        if ($terminType === 'termin_2') {
            return 'Termin 2';
        }

        return self::isWithoutWarranty($garansiMonths) ? 'Pembayaran' : 'Termin 1';
    }

    public static function shortStageLabel(string $terminType, ?int $garansiMonths): string
    {
        if ($terminType === 'termin_2') {
            return 'T2';
        }

        return self::isWithoutWarranty($garansiMonths) ? 'Pembayaran' : 'T1';
    }

    public static function bastLabel(string $terminType, ?int $garansiMonths, bool $includeLhpp = true): string
    {
        $base = $includeLhpp ? 'BAST/LHPP' : 'BAST';

        if ($terminType === 'termin_1' && self::isWithoutWarranty($garansiMonths)) {
            return $base;
        }

        return $base.' '.self::stageLabel($terminType, $garansiMonths);
    }

    public static function documentLabel(string $kind, string $terminType, ?int $garansiMonths): string
    {
        $label = strtoupper($kind);

        if ($terminType === 'termin_1' && self::isWithoutWarranty($garansiMonths)) {
            return $label;
        }

        return $label.' '.self::stageLabel($terminType, $garansiMonths);
    }

    public static function approvalDocumentNumber(
        string $nomorOrder,
        string $terminType,
        ?int $garansiMonths
    ): string {
        if ($terminType === 'termin_1' && self::isWithoutWarranty($garansiMonths)) {
            return $nomorOrder;
        }

        return trim($nomorOrder.' '.self::stageLabel($terminType, $garansiMonths));
    }

    public static function generatedBastPdfFilename(
        string $nomorOrder,
        string $terminType,
        ?int $garansiMonths
    ): string {
        $safeOrder = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $nomorOrder), '-');
        $safeOrder = $safeOrder !== '' ? $safeOrder : 'order';

        if ($terminType === 'termin_1' && self::isWithoutWarranty($garansiMonths)) {
            return 'bast-'.$safeOrder.'.pdf';
        }

        $termin = $terminType === 'termin_2' ? 'termin-2' : 'termin-1';

        return 'bast-'.$termin.'-'.$safeOrder.'.pdf';
    }
}
