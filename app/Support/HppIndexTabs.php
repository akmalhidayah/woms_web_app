<?php

namespace App\Support;

use App\Models\Hpp;
use Illuminate\Database\Eloquent\Builder;

class HppIndexTabs
{
    public const ACTION = 'action';

    public const IN_APPROVAL = 'in_approval';

    public const APPROVED = 'approved';

    public const HISTORY = 'history';

    public static function normalize(?string $tab): string
    {
        return array_key_exists((string) $tab, self::options())
            ? (string) $tab
            : self::ACTION;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::ACTION => 'Perlu Tindakan',
            self::IN_APPROVAL => 'Proses Approval',
            self::APPROVED => 'Approved',
            self::HISTORY => 'Riwayat',
        ];
    }

    public static function apply(Builder $query, string $tab): Builder
    {
        return match (self::normalize($tab)) {
            self::IN_APPROVAL => $query->where('status', Hpp::STATUS_IN_REVIEW),
            self::APPROVED => $query
                ->where('status', Hpp::STATUS_APPROVED)
                ->doesntHave('budgetVerification')
                ->doesntHave('purchaseOrder')
                ->doesntHave('lhppBasts'),
            self::HISTORY => $query
                ->where('status', Hpp::STATUS_APPROVED)
                ->where(function (Builder $builder): void {
                    $builder
                        ->whereHas('budgetVerification')
                        ->orWhereHas('purchaseOrder')
                        ->orWhereHas('lhppBasts');
                }),
            default => $query->whereIn('status', [
                Hpp::STATUS_DRAFT,
                Hpp::STATUS_REJECTED,
            ]),
        };
    }

    /**
     * @return array<string, int>
     */
    public static function counts(): array
    {
        $counts = [];

        foreach (array_keys(self::options()) as $tab) {
            $counts[$tab] = self::apply(Hpp::query(), $tab)->count();
        }

        return $counts;
    }

    public static function fromRequest(?string $tab, ?string $legacyStatus): string
    {
        if ($tab !== null && $tab !== '') {
            return self::normalize($tab);
        }

        return match ($legacyStatus) {
            Hpp::STATUS_DRAFT, Hpp::STATUS_REJECTED => self::ACTION,
            Hpp::STATUS_IN_REVIEW => self::IN_APPROVAL,
            Hpp::STATUS_APPROVED => self::APPROVED,
            default => self::ACTION,
        };
    }

    public static function emptyMessage(string $tab): string
    {
        return match (self::normalize($tab)) {
            self::IN_APPROVAL => 'Belum ada HPP dalam proses approval.',
            self::APPROVED => 'Belum ada HPP approved yang menunggu proses lanjutan.',
            self::HISTORY => 'Belum ada riwayat HPP.',
            default => 'Belum ada HPP yang perlu tindakan.',
        };
    }
}
