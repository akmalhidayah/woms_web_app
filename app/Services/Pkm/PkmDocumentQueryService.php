<?php

namespace App\Services\Pkm;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Models\LhppBast;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class PkmDocumentQueryService
{
    public function applyBaseScope(Builder $query): Builder
    {
        return $query->whereHas('purchaseOrder', fn (Builder $po): Builder => $po
            ->where('approve_manager', true)
            ->whereNotNull('purchase_order_number')
            ->whereRaw("TRIM(purchase_order_number) <> ''"))
            ->whereHas('lhppBasts', fn (Builder $bast): Builder => $bast
                ->where('termin_type', 'termin_1'));
    }

    public function applyStatusFilter(Builder $query, string $status): Builder
    {
        if (! in_array($status, ['complete', 'incomplete'], true)) {
            return $query;
        }

        $complete = function (Builder $builder): void {
            $builder
                ->whereHas('documents', fn (Builder $documents): Builder => $documents->where('jenis_dokumen', OrderDocumentType::Abnormalitas))
                ->whereHas('latestApprovedHpp')
                ->whereHas('purchaseOrder', fn (Builder $po): Builder => $po->whereNotNull('po_document_path'))
                ->whereHas('lhppBasts', fn (Builder $bast): Builder => $bast
                    ->where('termin_type', 'termin_1')
                    ->where('approval_status', LhppBast::APPROVAL_APPROVED)
                    ->whereHas('lpjPpl', fn (Builder $lpj): Builder => $lpj
                        ->whereNotNull('lpj_document_path_termin1')
                        ->whereNotNull('ppl_document_path_termin1')));
        };

        return $status === 'complete'
            ? $query->where($complete)
            : $query->whereNot($complete);
    }

    public function isEligibleOrder(Order $order): bool
    {
        return $this->applyBaseScope(Order::query()->whereKey($order->getKey()))->exists();
    }
}
