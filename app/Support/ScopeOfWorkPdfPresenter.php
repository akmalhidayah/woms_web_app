<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderScopeOfWork;
use App\Models\OutlineAgreement;

class ScopeOfWorkPdfPresenter
{
    public function creatorName(OrderScopeOfWork $scopeOfWork): string
    {
        return trim((string) ($scopeOfWork->creator?->name ?: $scopeOfWork->nama_penginput)) ?: '-';
    }

    public function creatorUnitLabel(Order $order): string
    {
        $candidates = [
            $order->latestHpp?->outlineAgreement?->unitWork?->name,
            $order->initialWork?->outlineAgreement?->unitWork?->name,
            $order->latestHpp?->unit_kerja_pengendali,
            $order->initialWork?->unit_kerja_pengendali,
            $this->activeOutlineAgreementUnitName(),
        ];

        foreach ($candidates as $candidate) {
            $label = trim((string) $candidate);

            if ($label !== '') {
                return $label;
            }
        }

        return '-';
    }

    private function activeOutlineAgreementUnitName(): ?string
    {
        return OutlineAgreement::query()
            ->with('unitWork:id,name')
            ->where('status', OutlineAgreement::STATUS_ACTIVE)
            ->latest('id')
            ->first(['id', 'unit_work_id'])
            ?->unitWork
            ?->name;
    }
}
