<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\OrderWorkshop;
use Illuminate\Database\Eloquent\Builder;

final class WorkshopReadiness
{
    public const WAITING_PREPARATION = 'waiting_preparation';

    public const COMPLETED = 'completed';

    /** @return array{code: string, label: string, can_advance: bool} */
    public function resolve(?OrderWorkshop $workshop): array
    {
        if ($workshop?->progress_status === OrderWorkshop::PROGRESS_DONE) {
            return $this->state(self::COMPLETED, 'Selesai', true);
        }

        return match ($workshop?->preparation_status) {
            OrderWorkshop::PREPARATION_WAITING_BUDGET_CONFIRMATION => $this->state(
                OrderWorkshop::PREPARATION_WAITING_BUDGET_CONFIRMATION,
                'Menunggu Konfirmasi Anggaran',
                false,
            ),
            OrderWorkshop::PREPARATION_WAITING_MATERIAL => $this->state(
                OrderWorkshop::PREPARATION_WAITING_MATERIAL,
                'Menunggu Material',
                false,
            ),
            OrderWorkshop::PREPARATION_WAITING_BUDGET_TRANSFER => $this->state(
                OrderWorkshop::PREPARATION_WAITING_BUDGET_TRANSFER,
                'Menunggu Transfer Budget',
                false,
            ),
            OrderWorkshop::PREPARATION_COMPLETED => $this->state(
                OrderWorkshop::PREPARATION_COMPLETED,
                'Persiapan Selesai',
                true,
            ),
            default => $this->state(self::WAITING_PREPARATION, 'Belum Memilih Persiapan', false),
        };
    }

    public function canAdvance(?OrderWorkshop $workshop): bool
    {
        return $this->resolve($workshop)['can_advance'];
    }

    public function requiresReadiness(string $progress): bool
    {
        return in_array($progress, [
            OrderWorkshop::PROGRESS_QUALITY_CONTROL,
            OrderWorkshop::PROGRESS_DONE,
        ], true);
    }

    public function applyIncomplete(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->whereDoesntHave('orderWorkshop')
                ->orWhereHas('orderWorkshop', function (Builder $workshop): void {
                    $workshop
                        ->where(function (Builder $progress): void {
                            $progress
                                ->whereNull('progress_status')
                                ->orWhere('progress_status', '!=', OrderWorkshop::PROGRESS_DONE);
                        })
                        ->where(function (Builder $incomplete): void {
                            $incomplete
                                ->whereNull('preparation_status')
                                ->orWhereRaw("TRIM(preparation_status) = ''")
                                ->orWhere('preparation_status', '!=', OrderWorkshop::PREPARATION_COMPLETED);
                        });
                });
        });
    }

    public function preparationLocked(?OrderWorkshop $workshop, bool $hasQualityControl = false, bool $hasHandover = false): bool
    {
        return $hasQualityControl
            || $hasHandover
            || in_array($workshop?->progress_status, [
                OrderWorkshop::PROGRESS_QUALITY_CONTROL,
                OrderWorkshop::PROGRESS_DONE,
            ], true);
    }

    /** @return array{code: string, label: string, can_advance: bool} */
    private function state(string $code, string $label, bool $canAdvance): array
    {
        return ['code' => $code, 'label' => $label, 'can_advance' => $canAdvance];
    }
}
