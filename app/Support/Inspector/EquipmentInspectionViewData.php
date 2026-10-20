<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionSignature;
use App\Models\User;
use App\Support\RecentApprovalSignatureResolver;

final class EquipmentInspectionViewData
{
    public static function make(array $form, ?EquipmentInspection $inspection = null, ?User $viewer = null): array
    {
        $values = [];
        $files = [];
        if ($inspection) {
            $inspection->load(['answers.attachments', 'signatures', 'unitLink.unit']);
            foreach ($inspection->answers as $answer) {
                $values[$answer->item_key] = ['rating' => $answer->rating ?? '', 'remark' => $answer->remark ?? ''];
                $files[$answer->item_key] = $answer->attachments;
            }
        } else {
            foreach ($form['groups'] as $group) {
                foreach ($group['items'] as $item) {
                    $values[$item['id']] = ['rating' => '', 'remark' => ''];
                }
            }
        }

        $readOnly = $inspection && ! $inspection->isDraft();
        $currentLink = $inspection?->unitLink;
        $formId = (int) ($form['master_id'] ?? 0);
        $units = $formId > 0
            ? app(EquipmentInspectionUnitRepository::class)->eligibleForForm($formId, $currentLink?->equipment_inspection_unit_id)
            : collect();
        $unitRequired = $currentLink !== null
            || (! $inspection && (bool) config('equipment-inspection.require_unit_for_new_inspections'));

        return [
            'form' => $form,
            'inspection' => $inspection,
            'values' => $values,
            'attachmentsByItem' => $files,
            'readOnly' => $readOnly,
            'recentSignatureDataUrl' => ! $readOnly && $viewer
                ? app(RecentApprovalSignatureResolver::class)->latestFullSignatureForUser($viewer)
                : null,
            'inspectionDate' => $inspection?->inspection_date->format('Y-m-d') ?? now(config('app.timezone'))->toDateString(),
            'today' => now(config('app.timezone'))->toDateString(),
            'inspectorSignature' => $inspection?->signatures->first(fn ($signature): bool => $signature->role_key === EquipmentInspectionSignature::ROLE_INSPECTOR
                && $signature->document_version === $inspection->document_version),
            'units' => $units,
            'selectedUnitId' => $currentLink?->equipment_inspection_unit_id,
            'selectedUnitSnapshot' => $currentLink ? [
                'asset_code' => $currentLink->unit_code_snapshot,
                'name' => $currentLink->unit_name_snapshot,
                'location' => $currentLink->unit?->location,
            ] : null,
            'unitRequired' => $unitRequired,
            'unitLocked' => (bool) ($inspection?->signed_at || $inspection?->document_no || $readOnly),
        ];
    }
}
