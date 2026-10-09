<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionAnswer;
use App\Models\EquipmentInspectionAttachment;

final class EquipmentInspectionSnapshot
{
    public static function payload(EquipmentInspection $inspection): array
    {
        $inspection->load(['answers.attachments']);

        return [
            'public_id' => $inspection->public_id,
            'document_version' => $inspection->document_version,
            'document_no' => $inspection->document_no,
            'form_slug' => $inspection->form_slug,
            'form_name' => $inspection->form_name,
            'template' => $inspection->template_snapshot,
            'template_hash' => $inspection->template_hash,
            'inspection_date' => $inspection->inspection_date->format('Y-m-d'),
            'inspector_user_id' => $inspection->inspector_user_id === null ? null : (int) $inspection->inspector_user_id,
            'inspector_name' => $inspection->inspector_name,
            'signed_at' => $inspection->signed_at?->toIso8601String(),
            'answers' => $inspection->answers->map(fn (EquipmentInspectionAnswer $answer): array => [
                'item_key' => $answer->item_key,
                'position' => (int) $answer->position,
                'item_label' => $answer->item_label,
                'group_key' => $answer->group_key,
                'group_label' => $answer->group_label,
                'rating' => $answer->rating,
                'remark' => $answer->remark,
                'attachments' => $answer->attachments->map(fn (EquipmentInspectionAttachment $file): array => [
                    'id' => (int) $file->id, 'sha256' => $file->sha256, 'mime_type' => $file->mime_type, 'size' => (int) $file->size,
                ])->all(),
            ])->all(),
        ];
    }

    public static function hash(array $payload): string
    {
        return hash('sha256', json_encode(self::canonical($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private static function canonical(array $payload): array
    {
        // MySQL JSON may reorder object keys; list ordering remains meaningful.
        if (! array_is_list($payload)) {
            ksort($payload, SORT_STRING);
        }
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = self::canonical($value);
            }
        }

        return $payload;
    }
}
