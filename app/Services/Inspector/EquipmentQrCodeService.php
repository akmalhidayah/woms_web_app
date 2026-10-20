<?php

namespace App\Services\Inspector;

use App\Models\EquipmentInspectionUnit;
use App\Support\Inspector\SimpleQrCode;
use InvalidArgumentException;

final class EquipmentQrCodeService
{
    public function __construct(private readonly SimpleQrCode $qr) {}

    public function publicUrl(EquipmentInspectionUnit $unit): string
    {
        $base = rtrim((string) config('equipment-inspection.public_base_url'), '/');
        if ($base === '' || ! filter_var($base, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Base URL publik QR belum valid. Periksa EQUIPMENT_INSPECTION_PUBLIC_BASE_URL atau APP_URL.');
        }
        if (app()->environment('production') && parse_url($base, PHP_URL_SCHEME) !== 'https') {
            throw new InvalidArgumentException('Base URL publik QR production wajib menggunakan HTTPS.');
        }

        return $base.route('public.equipment-history.show', ['publicCode' => $unit->public_code], false);
    }

    public function svg(EquipmentInspectionUnit $unit): string
    {
        return $this->qr->svg($this->publicUrl($unit));
    }
}
