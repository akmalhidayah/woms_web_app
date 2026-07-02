<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderScopeOfWork;

class ScopeOfWorkPdfPresenter
{
    public function creatorName(OrderScopeOfWork $scopeOfWork): string
    {
        return trim((string) ($scopeOfWork->creator?->name ?: $scopeOfWork->nama_penginput)) ?: '-';
    }

    public function creatorUnitLabel(Order $order): string
    {
        $candidates = [
            $order->latestHpp?->outlineAgreement?->jenis_kontrak,
            $order->initialWork?->outlineAgreement?->jenis_kontrak,
            $order->latestHpp?->seksi_pengendali,
            $order->initialWork?->seksi_pengendali,
            $order->seksi,
        ];

        foreach ($candidates as $candidate) {
            $label = trim((string) $candidate);

            if ($label !== '') {
                return $label;
            }
        }

        return '-';
    }
}
