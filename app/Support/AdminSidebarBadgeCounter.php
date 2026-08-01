<?php

namespace App\Support;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\LhppBast;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class AdminSidebarBadgeCounter
{
    public function __construct(
        private readonly BudgetVerificationIndexTabs $budgetVerificationTabs,
        private readonly PurchaseOrderIndexTabs $purchaseOrderTabs,
    ) {}

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        $orderJasaIncomplete = $this->orderJasaIncompleteCount();
        $createHpp = $this->createHppCount();
        $verifikasiAnggaran = $this->verifikasiAnggaranCount();
        $purchaseOrder = $this->purchaseOrderCount();
        $setGaransi = $this->setGaransiCount();
        $cekBast = $this->cekBastCount();
        $lpjPpl = $this->lpjPplCount();

        return [
            'order_jasa_incomplete' => $orderJasaIncomplete,
            'orders_total' => $orderJasaIncomplete,
            'create_hpp' => $createHpp,
            'verifikasi_anggaran' => $verifikasiAnggaran,
            'purchase_order' => $purchaseOrder,
            'set_garansi' => $setGaransi,
            'cek_bast' => $cekBast,
            'bast_total' => $setGaransi + $cekBast,
            'lpj_ppl' => $lpjPpl,
        ];
    }

    private function orderJasaIncompleteCount(): int
    {
        return Order::query()
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->whereDoesntHave('scopeOfWork')
            ->count();
    }

    private function createHppCount(): int
    {
        return Order::query()
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->whereHas('scopeOfWork')
            ->doesntHave('hpps')
            ->count();
    }

    private function verifikasiAnggaranCount(): int
    {
        return $this->budgetVerificationTabs->countFor(BudgetVerificationIndexTabs::TAB_ACTION);
    }

    private function purchaseOrderCount(): int
    {
        return $this->purchaseOrderTabs->countFor(PurchaseOrderIndexTabs::TAB_ACTION);
    }

    private function setGaransiCount(): int
    {
        return Order::query()
            ->where(function (Builder $query): void {
                // Mirrors Admin\GaransiController::garansiEligibleOrders().
                $query
                    ->whereHas('purchaseOrder', function (Builder $purchaseOrderQuery): void {
                        $purchaseOrderQuery
                            ->whereNotNull('purchase_order_number')
                            ->whereRaw("TRIM(purchase_order_number) <> ''")
                            ->where('progress_pekerjaan', 100);
                    })
                    ->orWhereHas('initialWork', fn (Builder $initialWorkQuery): Builder => $initialWorkQuery->where('progress_pekerjaan', 100));
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereDoesntHave('garansi')
                    ->orWhereHas('garansi', fn (Builder $garansiQuery): Builder => $garansiQuery->whereNull('garansi_months'));
            })
            ->count();
    }

    private function cekBastCount(): int
    {
        return LhppBast::query()
            ->where('termin_type', 'termin_1')
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('quality_control_status')
                    ->orWhereRaw("TRIM(quality_control_status) = ''")
                    ->orWhere('quality_control_status', 'pending');
            })
            ->count();
    }

    private function lpjPplCount(): int
    {
        return LhppBast::query()
            ->where('termin_type', 'termin_1')
            ->where(function (Builder $query): void {
                $query
                    ->where(function (Builder $terminOneQuery): void {
                        $terminOneQuery
                            ->whereDoesntHave('lpjPpl')
                            ->orWhereHas('lpjPpl', fn (Builder $lpjPplQuery): Builder => $this->whereAnyBlank($lpjPplQuery, [
                                'lpj_number_termin1',
                                'ppl_number_termin1',
                                'lpj_document_path_termin1',
                                'ppl_document_path_termin1',
                            ]));
                    })
                    ->orWhere(function (Builder $terminTwoQuery): void {
                        $terminTwoQuery
                            ->whereHas('garansi', fn (Builder $garansiQuery): Builder => $garansiQuery->where('garansi_months', '>', 0))
                            ->where(function (Builder $missingTerminTwoQuery): void {
                                $missingTerminTwoQuery
                                    ->whereDoesntHave('lpjPpl')
                                    ->orWhereHas('lpjPpl', fn (Builder $lpjPplQuery): Builder => $this->whereAnyBlank($lpjPplQuery, [
                                        'lpj_number_termin2',
                                        'ppl_number_termin2',
                                        'lpj_document_path_termin2',
                                        'ppl_document_path_termin2',
                                    ]));
                            });
                    });
            })
            ->count();
    }

    /**
     * @param  list<string>  $columns
     */
    private function whereAnyBlank(Builder $query, array $columns): Builder
    {
        return $query->where(function (Builder $blankQuery) use ($columns): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';

                $blankQuery->{$method}(function (Builder $columnQuery) use ($column): void {
                    $columnQuery
                        ->whereNull($column)
                        ->orWhereRaw("TRIM({$column}) = ''");
                });
            }
        });
    }
}
