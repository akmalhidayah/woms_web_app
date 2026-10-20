<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionAdminRead;
use App\Models\EquipmentInspectionSignature;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class EquipmentInspectionAdminUnread
{
    public function count(User $admin): int
    {
        return $this->query($admin)->count();
    }

    public function markRead(User $admin, EquipmentInspection $inspection): void
    {
        $wasSubmitted = $inspection->signatures()
            ->where('document_version', $inspection->document_version)
            ->where('role_key', EquipmentInspectionSignature::ROLE_INSPECTOR)
            ->exists();
        if (! $wasSubmitted || $inspection->trashed()) {
            return;
        }

        EquipmentInspectionAdminRead::query()->upsert([[
            'admin_user_id' => $admin->id,
            'equipment_inspection_id' => $inspection->id,
            'document_version' => $inspection->document_version,
            'viewed_at' => now(),
        ]], ['admin_user_id', 'equipment_inspection_id', 'document_version'], ['viewed_at']);
    }

    public function query(User $admin): Builder
    {
        return EquipmentInspection::query()
            ->whereNotNull('document_no')
            ->whereHas('signatures', fn (Builder $query): Builder => $query
                ->whereColumn('equipment_inspection_signatures.document_version', 'equipment_inspections.document_version')
                ->where('role_key', EquipmentInspectionSignature::ROLE_INSPECTOR))
            ->whereDoesntHave('adminReads', fn (Builder $query): Builder => $query
                ->where('admin_user_id', $admin->id)
                ->whereColumn('equipment_inspection_admin_reads.document_version', 'equipment_inspections.document_version'));
    }
}
