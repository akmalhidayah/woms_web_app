<?php

namespace App\Services\Inspector;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionApproval;
use App\Models\EquipmentInspectionSignature;
use App\Models\EquipmentInspectionUnit;
use App\Models\EquipmentInspectionUnitLink;
use App\Support\Inspector\EquipmentInspectionSnapshot;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

final class PublicEquipmentHistoryService
{
    public function isPublishable(EquipmentInspection $inspection, EquipmentInspectionUnit $unit): bool
    {
        if (! in_array($inspection->status, [EquipmentInspection::STATUS_MANAGER, EquipmentInspection::STATUS_APPROVED], true)) {
            return false;
        }

        $inspection->loadMissing([
            'unitLink',
            'approvals' => fn ($query) => $query->where('role_key', EquipmentInspectionSignature::ROLE_MANAGER),
            'signatures' => fn ($query) => $query->whereIn('role_key', [
                EquipmentInspectionSignature::ROLE_INSPECTOR,
                EquipmentInspectionSignature::ROLE_MANAGER,
            ]),
        ]);

        return $this->project($inspection, $unit) !== null;
    }

    /**
     * @return array{unit: array<string, mixed>, inspections: Paginator, latest: ?array<string, mixed>}
     */
    public function history(EquipmentInspectionUnit $unit): array
    {
        $unit->loadMissing('form.pic');
        $perPage = max(1, min(25, (int) config('equipment-inspection.public_history_per_page', 10)));
        $page = Paginator::resolveCurrentPage();
        $skip = ($page - 1) * $perPage;
        $safe = collect();
        $latest = null;
        $validCount = 0;
        $candidates = EquipmentInspection::query()
            ->select(['id', 'public_id', 'document_version', 'inspection_date', 'status'])
            ->whereIn('status', [EquipmentInspection::STATUS_MANAGER, EquipmentInspection::STATUS_APPROVED])
            ->whereHas('unitLink', fn ($query) => $query->where('equipment_inspection_unit_id', $unit->id))
            ->with([
                'unitLink',
                'signatures' => fn ($query) => $query->select([
                    'id', 'equipment_inspection_id', 'document_version', 'role_key', 'content_hash', 'signed_payload',
                ])->whereIn('role_key', [
                    EquipmentInspectionSignature::ROLE_INSPECTOR,
                    EquipmentInspectionSignature::ROLE_MANAGER,
                ]),
                'approvals' => fn ($query) => $query->select([
                    'id', 'equipment_inspection_id', 'document_version', 'step_order', 'role_key', 'status', 'token_hash',
                ])->where('role_key', EquipmentInspectionSignature::ROLE_MANAGER),
            ])
            ->orderByDesc('inspection_date')->orderByDesc('id');

        // Validate in bounded, eager-loaded batches. Stop after one extra valid row:
        // pagination must never count invalid snapshots or load all history at once.
        foreach ($candidates->lazy(50) as $inspection) {
            $projected = $this->project($inspection, $unit);
            if ($projected === null) {
                continue;
            }
            $latest ??= $projected;
            if ($validCount++ < $skip) {
                continue;
            }
            $safe->push($projected);
            if ($safe->count() > $perPage) {
                break;
            }
        }
        $paginator = (new Paginator($safe, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]))->withQueryString();

        return [
            'unit' => [
                'public_code' => $unit->public_code,
                'asset_code' => $unit->asset_code,
                'name' => $unit->form?->name ?: $unit->name,
                'location' => $unit->location,
                'lifecycle_status' => $unit->lifecycle_status,
                'has_image' => filled($unit->image_path) || filled($unit->form?->image_path),
                'pic' => $unit->form?->pic ? [
                    'name' => $unit->form->pic->name,
                    'avatar_url' => $unit->form->pic->avatar_url,
                    'avatar_position' => $unit->form->pic->avatar_object_position,
                ] : null,
            ],
            'inspections' => $paginator,
            'latest' => $latest,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function project(EquipmentInspection $inspection, EquipmentInspectionUnit $unit): ?array
    {
        $approved = $inspection->status === EquipmentInspection::STATUS_APPROVED;
        $inApproval = $inspection->status === EquipmentInspection::STATUS_MANAGER;
        if (! $approved && ! $inApproval) {
            return null;
        }

        $signatures = $inspection->signatures
            ->where('document_version', $inspection->document_version)
            ->keyBy('role_key');
        $inspector = $signatures->get(EquipmentInspectionSignature::ROLE_INSPECTOR);
        $manager = $signatures->get(EquipmentInspectionSignature::ROLE_MANAGER);
        if (! $inspector
            || ! is_string($inspector->content_hash)
            || ! is_array($inspector->signed_payload)
            || ! hash_equals($inspector->content_hash, EquipmentInspectionSnapshot::hash($inspector->signed_payload))
            || ($approved && (! $manager
                || ! is_string($manager->content_hash)
                || ! is_array($manager->signed_payload)
                || ! hash_equals($inspector->content_hash, $manager->content_hash)
                || ! hash_equals($manager->content_hash, EquipmentInspectionSnapshot::hash($manager->signed_payload))))) {
            return null;
        }
        if ($inApproval && ! $inspection->approvals->contains(fn (EquipmentInspectionApproval $approval): bool =>
            $approval->document_version === $inspection->document_version
            && $approval->step_order === EquipmentInspectionSignature::STEPS[EquipmentInspectionSignature::ROLE_MANAGER]
            && $approval->role_key === EquipmentInspectionSignature::ROLE_MANAGER
            && $approval->status === EquipmentInspectionApproval::PENDING
            && filled($approval->token_hash))) {
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
                'group' => is_string($answer['group_label'] ?? null) ? $answer['group_label'] : null,
                'rating' => $answer['rating'],
                'rating_label' => match ($answer['rating']) {
                    'A' => 'Normal',
                    'B' => 'Kurang Normal',
                    'C' => 'Rusak',
                },
                'remark' => filled($remark) ? $remark : null,
            ];
        });
        if ($items->isEmpty() || $items->contains(null)) {
            return null;
        }
        /** @var Collection<int, array{label: string, rating: string, rating_label: string, remark: ?string}> $items */
        $counts = [
            'A' => $items->where('rating', 'A')->count(),
            'B' => $items->where('rating', 'B')->count(),
            'C' => $items->where('rating', 'C')->count(),
        ];

        return [
            'public_id' => $inspection->public_id,
            'inspection_date' => $inspectionDate,
            'document_no' => $documentNo,
            'status' => $approved ? 'Disetujui' : 'Proses Approval',
            'is_approved' => $approved,
            'pdf_available' => true,
            'inspector_name' => is_string($payload['inspector_name'] ?? null) ? $payload['inspector_name'] : null,
            'counts' => $counts,
            'items' => $items->values()->all(),
            'summary' => $this->summary($items, $counts),
        ];
    }

    /**
     * @param  Collection<int, array{label: string, rating: string, rating_label: string, remark: ?string}>  $items
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
            ->map(fn (array $item): string => $item['rating_label'].' pada '.$item['label'])->values();
        $suffix = $findings->isEmpty() ? '.' : ': '.$findings->implode(', ').'.';

        return 'Dari '.$items->count().' item pemeriksaan, '.implode(', ', $parts).$suffix;
    }
}
