<?php

declare(strict_types=1);

namespace App\Services\BengkelTasks;

use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\QualityControlReport;
use App\Models\QualityControlSignature;
use Illuminate\Database\Eloquent\Builder;

final class WorkshopQualityControlQueue
{
    public const ACTION = 'action';

    public function query(): Builder
    {
        return Order::query()
            ->with(['orderWorkshop', 'latestQualityControlReport.signatures.signer'])
            ->whereHas('orderWorkshop', fn (Builder $query) => $query
                ->where('progress_status', OrderWorkshop::PROGRESS_QUALITY_CONTROL));
    }

    public function status(Order $order): array
    {
        $report = $order->latestQualityControlReport;

        if (! $report) {
            return ['key' => 'missing', 'label' => 'Perlu Pemeriksaan', 'tone' => 'amber', 'action' => true];
        }

        if ($report->status === QualityControlReport::STATUS_DRAFT) {
            return ['key' => 'draft', 'label' => 'Dalam Pemeriksaan', 'tone' => 'blue', 'action' => true];
        }

        if ($report->approvalCompleted()) {
            return ['key' => 'completed', 'label' => 'Selesai', 'tone' => 'emerald', 'action' => false];
        }

        $signatures = $report->signatures;
        $broken = $signatures->isEmpty() || $signatures->contains(fn (QualityControlSignature $signature): bool => $signature->status === QualityControlSignature::STATUS_MISSING
            || ($signature->status === QualityControlSignature::STATUS_PENDING && ! $signature->signer_user_id)
        );

        return $broken
            ? ['key' => 'broken', 'label' => 'Perlu Tindakan', 'tone' => 'rose', 'action' => true]
            : ['key' => 'approval', 'label' => 'Menunggu Approval', 'tone' => 'violet', 'action' => false];
    }

    public function actionCount(): int
    {
        return $this->query()->get()->filter(fn (Order $order): bool => $this->status($order)['action'])->count();
    }
}
