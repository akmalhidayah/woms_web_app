<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class EquipmentInspectionIndexTabs
{
    public static function options(): array
    {
        return ['new' => 'Perlu Tindakan', 'approval' => 'Proses Approval', 'history' => 'Riwayat'];
    }

    public static function normalize(?string $tab): string
    {
        if ($tab === 'completed') {
            return 'history';
        }
        return array_key_exists((string) $tab, self::options()) ? $tab : 'new';
    }

    public static function apply(Builder $query, string $tab): Builder
    {
        return match (self::normalize($tab)) {
            'new' => $query->whereIn('status', [EquipmentInspection::STATUS_DRAFT, EquipmentInspection::STATUS_REVISION, EquipmentInspection::STATUS_READY, EquipmentInspection::STATUS_LEADER]),
            'approval' => $query->where('status', EquipmentInspection::STATUS_MANAGER),
            'history' => $query->where('status', EquipmentInspection::STATUS_APPROVED),
        };
    }

    public static function counts(User $viewer): array
    {
        $counts = [];
        foreach (self::options() as $tab => $label) {
            $query = EquipmentInspection::query()->when(! $viewer->isAdmin(), fn (Builder $q) => $q->where('inspector_user_id', $viewer->id));
            $counts[$tab] = self::apply($query, $tab)->count();
        }

        return $counts;
    }
}
