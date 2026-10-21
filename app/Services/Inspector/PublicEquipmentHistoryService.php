<?php

namespace App\Services\Inspector;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionSignature;
use App\Models\EquipmentInspectionUnit;
use App\Models\EquipmentInspectionUnitLink;
use App\Support\Inspector\EquipmentInspectionSnapshot;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class PublicEquipmentHistoryService
{
    public function isPublishable(EquipmentInspection $inspection, EquipmentInspectionUnit $unit): bool
    {
        if ($inspection->status !== EquipmentInspection::STATUS_APPROVED) {
            return false;
        }

        $inspection->loadMissing([
            'unitLink',
            'signatures' => fn ($query) => $query->whereIn('role_key', [
                EquipmentInspectionSignature::ROLE_INSPECTOR,
                EquipmentInspectionSignature::ROLE_MANAGER,
            ]),
        ]);

        return $this->project($inspection, $unit) !== null;
    }

    /**
     * @return array{unit: array<string, mixed>, inspections: LengthAwarePaginator, latest: ?array<string, mixed>}
     */
    public function history(EquipmentInspectionUnit $unit): array
    {
        $unit->loadMissing('form');
        $perPage = max(1, min(25, (int) config('equipment-inspection.public_history_per_page', 10)));
        $paginator = EquipmentInspection::query()
            ->where('status', EquipmentInspection::STATUS_APPROVED)
            ->whereHas('unitLink', fn ($query) => $query->where('equipment_inspection_unit_id', $unit->id))
            ->with([
                'unitLink',
                'signatures' => fn ($query) => $query->whereIn('role_key', [
                    EquipmentInspectionSignature::ROLE_INSPECTOR,
                    EquipmentInspectionSignature::ROLE_MANAGER,
                ]),
            ])
            ->orderByDesc('inspection_date')->orderByDesc('id')
            ->paginate($perPage)->withQueryString();

        $safe = $paginator->getCollection()
            ->map(fn (EquipmentInspection $inspection): ?array => $this->project($inspection, $unit))
            ->filter()->values();
        $paginator->setCollection($safe);

        return [
            'unit' => [
                'public_code' => $unit->public_code,
                'asset_code' => $unit->asset_code,
                'name' => $unit->form?->name ?: $unit->name,
                'location' => $unit->location,
                'lifecycle_status' => $unit->lifecycle_status,
                'has_image' => filled($unit->image_path) || filled($unit->form?->image_path),
            ],
            'inspections' => $paginator,
            'latest' => $paginator->currentPage() === 1 ? $safe->first() : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function project(EquipmentInspection $inspection, EquipmentInspectionUnit $unit): ?array
    {
        $signatures = $inspection->signatures
            ->where('document_version', $inspection->document_version)
            ->keyBy('role_key');
        $inspector = $signatures->get(EquipmentInspectionSignature::ROLE_INSPECTOR);
        $manager = $signatures->get(EquipmentInspectionSignature::ROLE_MANAGER);
        if (! $inspector || ! $manager
            || ! is_string($inspector->content_hash) || ! is_string($manager->content_hash)
            || ! is_array($inspector->signed_payload) || ! is_array($manager->signed_payload)
            || ! hash_equals($inspector->content_hash, $manager->content_hash)
            || ! hash_equals($inspector->content_hash, EquipmentInspectionSnapshot::hash($inspector->signed_payload))
            || ! hash_equals($manager->content_hash, EquipmentInspectionSnapshot::hash($manager->signed_payload))) {
            return null;
        }

        $payload = $inspector->signed_payload;
        if ((int) ($payload['document_version'] ?? 0) !== (int) $inspection->document_version
            || ! is_array($payload['answers'] ?? null)) {
            return null;
        }
        $link = $inspection->unitLink;
        if (! $link || (int) $link->equipment_inspection_unit_id !== (int) $unit->id) {
            return null;
        }
        $signedUnit = $payload['unit'] ?? null;
        if ($link->link_source === EquipmentInspectionUnitLink::SOURCE_DIRECT && ! is_array($signedUnit)) {
            return null;
        }
        if ($signedUnit !== null && (! is_array($signedUnit)
            || (int) ($signedUnit['id'] ?? 0) !== (int) $unit->id
            || ($signedUnit['asset_code'] ?? null) !== $link->unit_code_snapshot
            || ($signedUnit['name'] ?? null) !== $link->unit_name_snapshot)) {
            return null;
        }
        $inspectionDate = $payload['inspection_date'] ?? null;
        if (! is_string($inspectionDate) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $inspectionDate, $dateParts) !== 1
            || ! checkdate((int) $dateParts[2], (int) $dateParts[3], (int) $dateParts[1])) {
            return null;
        }
        $documentNo = $payload['document_no'] ?? null;
        if ($documentNo !== null && ! is_string($documentNo)) {
            return null;
        }
        $items = collect($payload['answers'])->map(function ($answer): ?array {
            if (! is_array($answer) || ! is_string($answer['item_label'] ?? null)
                || ! in_array($answer['rating'] ?? null, ['A', 'B', 'C'], true)) {
                return null;
            }

            $remark = $answer['remark'] ?? null;
            if ($remark !== null && ! is_string($remark)) {
                return null;
            }

            return [
                'label' => $answer['item_label'],
                'rating' => $answer['rating'],
                'remark' => filled($remark) ? trim($remark) : null,
            ];
        });
        if ($items->isEmpty() || $items->contains(null)) {
            return null;
        }
        /** @var Collection<int, array{label: string, rating: string, remark: ?string}> $items */
        $counts = [
            'A' => $items->where('rating', 'A')->count(),
            'B' => $items->where('rating', 'B')->count(),
            'C' => $items->where('rating', 'C')->count(),
        ];

        return [
            'public_id' => $inspection->public_id,
            'inspection_date' => $inspectionDate,
            'document_no' => $documentNo,
            'status' => 'Disetujui',
            'counts' => $counts,
            'items' => $items->values()->all(),
            'summary' => $this->summary($items, $counts),
        ];
    }

    /**
     * @param  Collection<int, array{label: string, rating: string, remark: ?string}>  $items
     * @param  array{A: int, B: int, C: int}  $counts
     */
    private function summary(Collection $items, array $counts): string
    {
        $parts = [
            $counts['A'].' Normal',
            $counts['B'].' Kurang Normal',
            $counts['C'].' Rusak',
        ];
        $findings = $items->whereIn('rating', ['B', 'C'])
            ->map(fn (array $item): string => $item['rating'].' pada '.$item['label'])->values();
        $suffix = $findings->isEmpty() ? '.' : ': '.$findings->implode(', ').'.';

        return 'Dari '.$items->count().' item pemeriksaan, '.implode(', ', $parts).$suffix;
    }
}
