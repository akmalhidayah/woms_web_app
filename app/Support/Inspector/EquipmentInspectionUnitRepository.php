<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspectionUnit;
use Illuminate\Support\Collection;

final class EquipmentInspectionUnitRepository
{
    /**
     * @return Collection<int, EquipmentInspectionUnit>
     */
    public function eligibleForForm(int $formId, ?int $includeUnitId = null): Collection
    {
        return EquipmentInspectionUnit::query()
            ->where('equipment_inspection_form_id', $formId)
            ->where(function ($query) use ($includeUnitId): void {
                $query->active();
                if ($includeUnitId) {
                    $query->orWhere('id', $includeUnitId);
                }
            })
            ->orderBy('sort_order')->orderBy('asset_code')->orderBy('id')
            ->get(['id', 'asset_code', 'name', 'location', 'lifecycle_status']);
    }
}
