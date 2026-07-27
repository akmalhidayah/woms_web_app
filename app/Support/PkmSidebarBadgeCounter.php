<?php

namespace App\Support;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\LhppBast;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class PkmSidebarBadgeCounter
{
    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        $lhppTerminOne = $this->lhppTerminOneCount();
        $lhppTerminTwo = $this->lhppTerminTwoCount();

        return [
            'create_hpp' => $this->createHppCount(),
            'jobwaiting' => $this->jobWaitingCount(),
            'lhpp' => $lhppTerminOne + $lhppTerminTwo,
            'lhpp_termin_1' => $lhppTerminOne,
            'lhpp_termin_2' => $lhppTerminTwo,
            'documents' => $this->documentsCount(),
        ];
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

    private function jobWaitingCount(): int
    {
        return Order::query()
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('purchaseOrder', function (Builder $purchaseOrderQuery): void {
                        $purchaseOrderQuery
                            ->where('approve_manager', true)
                            ->whereNotNull('purchase_order_number')
                            ->whereRaw("TRIM(purchase_order_number) <> ''");
                    })
                    ->orWhere(function (Builder $emergencyQuery): void {
                        $emergencyQuery
                            ->whereIn('prioritas', [
                                Order::PRIORITY_URGENT,
                                Order::PRIORITY_HIGH,
                            ])
                            ->has('initialWork');
                    });
            })
            ->where(function (Builder $query): void {
                $query
                    ->doesntHave('latestHpp')
                    ->orWhereDoesntHave('lhppBasts', function (Builder $bastQuery): void {
                        $bastQuery
                            ->where('termin_type', 'termin_1')
                            ->whereHas('garansi')
                            ->whereHas('lpjPpl', function (Builder $lpjPplQuery): void {
                                $lpjPplQuery
                                    ->whereNotNull('lpj_document_path_termin1')
                                    ->whereNotNull('ppl_document_path_termin1');
                            });
                    });
            })
            ->count();
    }

    private function lhppTerminOneCount(): int
    {
        return Order::query()
            ->where(function (Builder $query): void {
                // Mirrors Pkm\LhppController::eligibleOrders().
                $query
                    ->whereHas('purchaseOrder', function (Builder $purchaseOrderQuery): void {
                        $purchaseOrderQuery
                            ->whereNotNull('purchase_order_number')
                            ->whereRaw("TRIM(purchase_order_number) <> ''")
                            ->where('progress_pekerjaan', 100);
                    })
                    ->orWhereHas('initialWork', fn (Builder $initialWorkQuery): Builder => $initialWorkQuery->where('progress_pekerjaan', 100));
            })
            ->whereHas('latestApprovedHpp')
            ->whereHas('garansi')
            ->whereDoesntHave('lhppBasts', fn (Builder $bastQuery): Builder => $bastQuery->where('termin_type', 'termin_1'))
            ->count();
    }

    private function lhppTerminTwoCount(): int
    {
        return LhppBast::query()
            ->where('termin_type', 'termin_1')
            ->where('termin1_status', 'sudah')
            ->whereHas('garansi', fn (Builder $garansiQuery): Builder => $garansiQuery->where('garansi_months', '>', 0))
            ->whereDoesntHave('terminTwo')
            ->count();
    }

    private function documentsCount(): int
    {
        return Order::query()
            ->whereHas('purchaseOrder', function (Builder $purchaseOrderQuery): void {
                $purchaseOrderQuery
                    ->where('approve_manager', true)
                    ->whereNotNull('purchase_order_number')
                    ->whereRaw("TRIM(purchase_order_number) <> ''");
            })
            ->whereHas('lhppBasts', fn (Builder $bastQuery): Builder => $bastQuery
                ->where('termin_type', 'termin_1'))
            ->where(function (Builder $query): void {
                $query
                    ->whereDoesntHave('documents', fn (Builder $documentQuery): Builder => $documentQuery->where('jenis_dokumen', OrderDocumentType::Abnormalitas->value))
                    ->orWhereDoesntHave('latestApprovedHpp')
                    ->orWhereDoesntHave('latestPurchaseOrder', function (Builder $purchaseOrderQuery): void {
                        $purchaseOrderQuery
                            ->whereNotNull('po_document_path')
                            ->whereRaw("TRIM(po_document_path) <> ''");
                    })
                    ->orWhereDoesntHave('lhppBasts', function (Builder $bastQuery): void {
                        $bastQuery
                            ->where('termin_type', 'termin_1')
                            ->whereHas('lpjPpl', function (Builder $lpjPplQuery): void {
                                $lpjPplQuery
                                    ->whereNotNull('lpj_document_path_termin1')
                                    ->whereRaw("TRIM(lpj_document_path_termin1) <> ''")
                                    ->whereNotNull('ppl_document_path_termin1')
                                    ->whereRaw("TRIM(ppl_document_path_termin1) <> ''");
                            });
                    });
            })
            ->count();
    }
}
