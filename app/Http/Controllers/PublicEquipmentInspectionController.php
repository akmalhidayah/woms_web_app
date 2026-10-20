<?php

namespace App\Http\Controllers;

use App\Models\EquipmentInspectionUnit;
use App\Services\Inspector\PublicEquipmentHistoryService;
use App\Support\Inspector\EquipmentInspectionFormImageStorage;
use App\Support\Inspector\EquipmentInspectionUnitImageStorage;
use Symfony\Component\HttpFoundation\Response;

class PublicEquipmentInspectionController extends Controller
{
    public function show(string $publicCode, PublicEquipmentHistoryService $history): Response
    {
        $unit = $this->publishedUnit($publicCode);

        return response()->view('public.equipment-history', $history->history($unit), 200, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    public function image(
        string $publicCode,
        EquipmentInspectionUnitImageStorage $unitImages,
        EquipmentInspectionFormImageStorage $formImages,
    ): Response {
        $unit = $this->publishedUnit($publicCode);
        $unit->loadMissing('form');
        if ($unit->image_path && $unit->image_sha256) {
            $binary = $unitImages->read($unit->image_path, $unit->image_sha256);
            $mime = $unit->image_mime_type ?: 'image/jpeg';
        } elseif ($unit->form?->image_path && $unit->form?->image_sha256) {
            $binary = $formImages->read($unit->form->image_path, $unit->form->image_sha256);
            $mime = $unit->form->image_mime_type ?: 'image/jpeg';
        } else {
            abort(404);
        }

        return response($binary, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function publishedUnit(string $publicCode): EquipmentInspectionUnit
    {
        return EquipmentInspectionUnit::query()
            ->where('public_code', $publicCode)
            ->where('public_enabled', true)
            ->where('lifecycle_status', '!=', EquipmentInspectionUnit::STATUS_RETIRED)
            ->firstOrFail();
    }
}
