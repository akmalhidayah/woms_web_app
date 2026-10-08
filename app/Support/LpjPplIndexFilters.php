<?php

namespace App\Support;

use App\Models\LhppBast;
use Illuminate\Database\Eloquent\Builder;

class LpjPplIndexFilters
{
    public const TAB_ACTION = 'action';

    public const TAB_LPJ_COMPLETE = 'lpj_complete';

    public const TAB_DOCUMENTS_COMPLETE = 'documents_complete';

    public const TAB_COMPLETED = 'completed';

    public const STAGE_ALL = 'all';

    public const STAGE_PAYMENT = 'payment';

    public const STAGE_TERMIN_ONE = 'termin_1';

    public const STAGE_TERMIN_TWO = 'termin_2';

    /** @return array<string, string> */
    public function tabOptions(): array
    {
        return [
            self::TAB_ACTION => 'Perlu Tindakan',
            self::TAB_LPJ_COMPLETE => 'Sudah LPJ',
            self::TAB_DOCUMENTS_COMPLETE => 'Sudah PPL',
            self::TAB_COMPLETED => 'Selesai',
        ];
    }

    /** @return array<string, string> */
    public function stageOptions(): array
    {
        return [
            self::STAGE_ALL => 'Semua Tahap',
            self::STAGE_PAYMENT => 'Pembayaran',
            self::STAGE_TERMIN_ONE => 'Termin 1',
            self::STAGE_TERMIN_TWO => 'Termin 2',
        ];
    }

    public function normalizeTab(?string $tab): string
    {
        return array_key_exists((string) $tab, $this->tabOptions())
            ? (string) $tab
            : self::TAB_ACTION;
    }

    public function normalizeStage(?string $stage): string
    {
        return array_key_exists((string) $stage, $this->stageOptions())
            ? (string) $stage
            : self::STAGE_ALL;
    }

    public function apply(Builder $query, string $tab, string $stage): Builder
    {
        $tab = $this->normalizeTab($tab);
        $stage = $this->normalizeStage($stage);

        return $this->applyStage($query, $stage, function (Builder $stageQuery, int $termin) use ($tab): void {
            $this->applyTab($stageQuery, $tab, $termin);
        });
    }

    /** @return array<string, int> */
    public function counts(string $stage): array
    {
        return collect(array_keys($this->tabOptions()))
            ->mapWithKeys(fn (string $tab): array => [
                $tab => $this->apply($this->baseQuery(), $tab, $stage)->count(),
            ])
            ->all();
    }

    private function baseQuery(): Builder
    {
        return LhppBast::query()
            ->where('termin_type', 'termin_1')
            ->where('approval_status', LhppBast::APPROVAL_APPROVED);
    }

    private function applyTab(Builder $query, string $tab, int $termin): void
    {
        $suffix = "termin{$termin}";
        $lpjFields = ["lpj_number_{$suffix}", "lpj_document_path_{$suffix}"];
        $pplFields = ["ppl_number_{$suffix}", "ppl_document_path_{$suffix}"];
        $allFields = [...$lpjFields, ...$pplFields];
        $paymentField = "termin{$termin}_status";

        match ($tab) {
            self::TAB_LPJ_COMPLETE => $query
                ->whereHas('lpjPpl', fn (Builder $lpjPpl): Builder => $this->whereAllFilled($lpjPpl, $lpjFields))
                ->where(fn (Builder $builder): Builder => $this->whereRelationMissingFields($builder, $pplFields)),
            self::TAB_DOCUMENTS_COMPLETE => $query
                ->whereHas('lpjPpl', fn (Builder $lpjPpl): Builder => $this->whereAllFilled($lpjPpl, $allFields))
                ->where(fn (Builder $builder): Builder => $builder
                    ->whereNull($paymentField)
                    ->orWhere($paymentField, '!=', 'sudah')),
            self::TAB_COMPLETED => $query
                ->whereHas('lpjPpl', fn (Builder $lpjPpl): Builder => $this->whereAllFilled($lpjPpl, $allFields))
                ->where($paymentField, 'sudah'),
            default => $query->where(fn (Builder $builder): Builder => $this->whereRelationMissingFields($builder, $lpjFields)),
        };
    }

    /**
     * @param  callable(Builder, int): void  $callback
     */
    private function applyStage(Builder $query, string $stage, callable $callback): Builder
    {
        if ($stage === self::STAGE_PAYMENT) {
            return $query
                ->whereHas('garansi', fn (Builder $garansi): Builder => $garansi->where('garansi_months', 0))
                ->where(function (Builder $builder) use ($callback): void {
                    $callback($builder, 1);
                });
        }

        if ($stage === self::STAGE_TERMIN_ONE) {
            return $query
                ->whereDoesntHave('garansi', fn (Builder $garansi): Builder => $garansi->where('garansi_months', 0))
                ->where(function (Builder $builder) use ($callback): void {
                    $callback($builder, 1);
                });
        }

        if ($stage === self::STAGE_TERMIN_TWO) {
            return $this->applyTerminTwoEligibility($query)
                ->where(function (Builder $builder) use ($callback): void {
                    $callback($builder, 2);
                });
        }

        return $query->where(function (Builder $builder) use ($callback): void {
            $builder
                ->where(function (Builder $terminTwo) use ($callback): void {
                    $this->applyTerminTwoEligibility($terminTwo)
                        ->where(function (Builder $stageQuery) use ($callback): void {
                            $callback($stageQuery, 2);
                        });
                })
                ->orWhere(function (Builder $terminOne) use ($callback): void {
                    $terminOne
                        ->whereNot(fn (Builder $eligible): Builder => $this->applyTerminTwoEligibility($eligible))
                        ->where(function (Builder $stageQuery) use ($callback): void {
                            $callback($stageQuery, 1);
                        });
                });
        });
    }

    private function applyTerminTwoEligibility(Builder $query): Builder
    {
        return $query
            ->where('termin1_status', 'sudah')
            ->whereHas('garansi', fn (Builder $garansi): Builder => $garansi->where('garansi_months', '>', 0))
            ->whereHas('terminTwo', fn (Builder $terminTwo): Builder => $terminTwo
                ->where('approval_status', LhppBast::APPROVAL_APPROVED));
    }

    /** @param list<string> $fields */
    private function whereRelationMissingFields(Builder $query, array $fields): Builder
    {
        return $query
            ->whereDoesntHave('lpjPpl')
            ->orWhereHas('lpjPpl', fn (Builder $lpjPpl): Builder => $this->whereAnyBlank($lpjPpl, $fields));
    }

    /** @param list<string> $fields */
    private function whereAllFilled(Builder $query, array $fields): Builder
    {
        foreach ($fields as $field) {
            $query->whereNotNull($field)->whereRaw("TRIM({$field}) <> ''");
        }

        return $query;
    }

    /** @param list<string> $fields */
    private function whereAnyBlank(Builder $query, array $fields): Builder
    {
        return $query->where(function (Builder $builder) use ($fields): void {
            foreach ($fields as $field) {
                $builder->orWhereNull($field)->orWhereRaw("TRIM({$field}) = ''");
            }
        });
    }
}
