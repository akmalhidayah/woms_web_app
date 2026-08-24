<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class GaransiIndexTabs
{
    public const TAB_ACTION = 'action';

    public const TAB_SET = 'set';

    /** @return array<string, string> */
    public function options(): array
    {
        return [
            self::TAB_ACTION => 'Perlu Tindakan',
            self::TAB_SET => 'Sudah Set',
        ];
    }

    public function normalize(?string $tab): string
    {
        return array_key_exists((string) $tab, $this->options())
            ? (string) $tab
            : self::TAB_ACTION;
    }

    public function apply(Builder $query, string $tab): Builder
    {
        if ($this->normalize($tab) === self::TAB_SET) {
            return $query->whereHas('garansi', fn (Builder $garansi): Builder => $garansi
                ->whereNotNull('garansi_months'));
        }

        return $query->where(function (Builder $builder): void {
            $builder
                ->whereDoesntHave('garansi')
                ->orWhereHas('garansi', fn (Builder $garansi): Builder => $garansi
                    ->whereNull('garansi_months'));
        });
    }

    /**
     * @return array<string, int>
     */
    public function counts(Builder $eligibleOrders): array
    {
        return collect(array_keys($this->options()))
            ->mapWithKeys(fn (string $tab): array => [
                $tab => $this->apply(clone $eligibleOrders, $tab)->count(),
            ])
            ->all();
    }
}
