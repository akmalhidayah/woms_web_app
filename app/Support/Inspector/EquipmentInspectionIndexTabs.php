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
        foreach (array_keys(self::options()) as $tab) {
            $counts[$tab] = self::count($viewer, $tab);
        }

        return $counts;
    }

    public static function count(User $viewer, string $tab): int
    {
        $query = EquipmentInspection::query()
            ->when(! $viewer->isAdmin(), fn (Builder $builder) => $builder->where('inspector_user_id', $viewer->id));

        return self::apply($query, $tab)->count();
    }
}
