<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\OrderWorkshop;
use Illuminate\Database\Eloquent\Builder;

final class WorkshopReadiness
{
    public const WAITING_CONFIRMATION = 'waiting_confirmation';

    public const WAITING_BUDGET = 'waiting_budget';

    public const WAITING_MATERIAL_STATUS = 'waiting_material_status';

    public const READY = 'ready';

    public const COMPLETED = 'completed';

    /** @return array{code: string, label: string, can_advance: bool} */
    public function resolve(?OrderWorkshop $workshop): array
    {
        if ($workshop?->progress_status === OrderWorkshop::PROGRESS_DONE) {
            return $this->state(self::COMPLETED, 'Selesai', true);
        }

        if (! $workshop || blank($workshop->konfirmasi_anggaran)) {
            return $this->state(self::WAITING_CONFIRMATION, 'Menunggu Konfirmasi', false);
        }

        if ($workshop->konfirmasi_anggaran === OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY) {
            return $workshop->status_anggaran === OrderWorkshop::STATUS_ANGGARAN_COMPLETE_TRANSFER
                ? $this->state(self::READY, 'Siap Diproses', true)
                : $this->state(self::WAITING_BUDGET, 'Menunggu Anggaran', false);
        }

        if ($workshop->konfirmasi_anggaran === OrderWorkshop::KONFIRMASI_MATERIAL_READY) {
            return filled($workshop->status_material)
                ? $this->state(self::READY, 'Siap Diproses', true)
                : $this->state(self::WAITING_MATERIAL_STATUS, 'Menunggu Status Material', false);
        }

        return $this->state(self::WAITING_CONFIRMATION, 'Menunggu Konfirmasi', false);
    }

    public function canAdvance(?OrderWorkshop $workshop): bool
    {
        return $this->resolve($workshop)['can_advance'];
    }

    public function requiresReadiness(string $progress): bool
    {
        return in_array($progress, [
            OrderWorkshop::PROGRESS_IN_PROGRESS,
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
                                ->whereNull('konfirmasi_anggaran')
                                ->orWhereRaw("TRIM(konfirmasi_anggaran) = ''")
                                ->orWhere(function (Builder $notReady): void {
                                    $notReady
                                        ->where('konfirmasi_anggaran', OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY)
                                        ->where(function (Builder $status): void {
                                            $status
                                                ->whereNull('status_anggaran')
                                                ->orWhereRaw("TRIM(status_anggaran) = ''")
                                                ->orWhere('status_anggaran', OrderWorkshop::STATUS_ANGGARAN_WAITING_BUDGET);
                                        });
                                })
                                ->orWhere(function (Builder $ready): void {
                                    $ready
                                        ->where('konfirmasi_anggaran', OrderWorkshop::KONFIRMASI_MATERIAL_READY)
                                        ->where(function (Builder $status): void {
                                            $status->whereNull('status_material')->orWhereRaw("TRIM(status_material) = ''");
                                        });
                                });
                        });
                });
        });
    }

    /** @return array{code: string, label: string, can_advance: bool} */
    private function state(string $code, string $label, bool $canAdvance): array
    {
        return ['code' => $code, 'label' => $label, 'can_advance' => $canAdvance];
    }
}
