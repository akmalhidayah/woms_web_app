<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionSignature;

final class EquipmentInspectionViewData
{
    public static function make(array $form, ?EquipmentInspection $inspection = null): array
    {
        $values = [];
        $files = [];
        if ($inspection) {
            $inspection->load(['answers.attachments', 'signatures']);
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

        return [
            'form' => $form,
            'inspection' => $inspection,
            'values' => $values,
            'attachmentsByItem' => $files,
            'readOnly' => $inspection && ! $inspection->isDraft(),
            'inspectionDate' => $inspection?->inspection_date->format('Y-m-d') ?? now(config('app.timezone'))->toDateString(),
            'today' => now(config('app.timezone'))->toDateString(),
            'inspectorSignature' => $inspection?->signatures->first(fn ($signature): bool => $signature->role_key === EquipmentInspectionSignature::ROLE_INSPECTOR
                && $signature->document_version === $inspection->document_version),
        ];
    }
}
