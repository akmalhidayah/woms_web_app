<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class EquipmentInspectionIndexTabs
{
    public static function options(): array
    {
        return ['new' => 'Baru Masuk', 'approval' => 'Proses Approval', 'completed' => 'Selesai', 'history' => 'Riwayat'];
    }

    public static function normalize(?string $tab): string
    {
        return array_key_exists((string) $tab, self::options()) ? $tab : 'new';
    }

    public static function apply(Builder $query, string $tab, User $admin): Builder
    {
        return match (self::normalize($tab)) {
            'new' => $query->whereNotNull('signed_at')->whereNotExists(function ($read) use ($admin): void {
                $read->selectRaw('1')->from('equipment_inspection_admin_reads')->where('admin_user_id', $admin->id)
                    ->whereColumn('equipment_inspection_id', 'equipment_inspections.id')
                    ->whereColumn('document_version', 'equipment_inspections.document_version');
            }),
            'approval' => $query->whereIn('status', [EquipmentInspection::STATUS_READY, EquipmentInspection::STATUS_MANAGER, EquipmentInspection::STATUS_LEADER]),
            'completed' => $query->where('status', EquipmentInspection::STATUS_APPROVED),
            default => $query,
        };
    }

    public static function counts(User $admin): array
    {
        $counts = [];
        foreach (self::options() as $tab => $label) {
            $counts[$tab] = self::apply(EquipmentInspection::query(), $tab, $admin)->count();
        }

        return $counts;
    }
}
