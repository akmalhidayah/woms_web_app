<?php

namespace App\Services\Orders;

use App\Models\BengkelTask;
use App\Models\BudgetVerification;
use App\Models\Garansi;
use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\InitialWork;
use App\Models\InitialWorkSignature;
use App\Models\LhppBast;
use App\Models\LhppBastImage;
use App\Models\LhppBastSignature;
use App\Models\LpjPpl;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderScopeOfWork;
use App\Models\OrderWorkshop;
use App\Models\PurchaseOrder;
use App\Models\QualityControlReport;
use App\Models\QualityControlReportFile;
use App\Models\QualityControlSignature;
use App\Models\WorkshopHandover;
use App\Models\WorkshopWorkPackage;
use App\Models\WorkshopWorkPackageAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Deletes an Order and its owned records in dependency order.
 *
 * The production orders table can use MyISAM, so the transaction below does
 * not make the complete cross-engine operation atomic. It still ensures a
 * deterministic child-first sequence and leaves the parent for last.
 */
final class OrderDeletionService
{
    public function delete(Order $order): void
    {
        $orderId = (int) $order->getKey();
        $paths = [];

        $tasks = BengkelTask::query()
            ->where(function ($query) use ($orderId): void {
                $query->where('order_id', $orderId)
                    ->orWhere('archived_order_id', $orderId);
            })
            ->get(['id', 'attachment_path']);
        $taskIds = $tasks->pluck('id')->all();
        $paths = array_merge($paths, $tasks->pluck('attachment_path')->filter()->all());

        $packages = WorkshopWorkPackage::query()
            ->where('order_id', $orderId)
            ->get(['id']);
        $packageIds = $packages->pluck('id')->all();

        $reports = QualityControlReport::query()
            ->where(function ($query) use ($orderId, $taskIds): void {
                $query->where('order_id', $orderId);

                if ($taskIds !== []) {
                    $query->orWhereIn('bengkel_task_id', $taskIds);
                }
            })
            ->get(['id', 'payload']);
        $reportIds = $reports->pluck('id')->all();
        foreach ($reports as $report) {
            $paths = array_merge($paths, $this->pathsFromPayload($report->payload));
        }

        if ($reportIds !== []) {
            $paths = array_merge(
                $paths,
                QualityControlReportFile::query()->whereIn('quality_control_report_id', $reportIds)->pluck('file_path')->all(),
            );
        }

        $initialWorks = InitialWork::query()
            ->where('order_id', $orderId)
            ->get(['id']);
        $initialWorkIds = $initialWorks->pluck('id')->all();
        if ($initialWorkIds !== []) {
            $paths = array_merge(
                $paths,
                InitialWorkSignature::query()->whereIn('initial_work_id', $initialWorkIds)->pluck('signature_path')->all(),
            );
        }

        $hpps = Hpp::query()->where('order_id', $orderId)->get(['id']);
        $hppIds = $hpps->pluck('id')->all();
        if ($hppIds !== []) {
            $paths = array_merge(
                $paths,
                HppSignature::query()->whereIn('hpp_id', $hppIds)->pluck('signed_document_path')->all(),
            );
        }

        $purchaseOrders = PurchaseOrder::query()
            ->where('order_id', $orderId)
            ->when($hppIds !== [], fn ($query) => $query->orWhereIn('hpp_id', $hppIds))
            ->get(['id', 'po_document_path']);
        $purchaseOrderIds = $purchaseOrders->pluck('id')->all();
        $paths = array_merge($paths, $purchaseOrders->pluck('po_document_path')->filter()->all());

        $basts = LhppBast::query()
            ->where('order_id', $orderId)
            ->when($hppIds !== [], fn ($query) => $query->orWhereIn('hpp_id', $hppIds))
            ->when($purchaseOrderIds !== [], fn ($query) => $query->orWhereIn('purchase_order_id', $purchaseOrderIds))
            ->get(['id', 'parent_lhpp_bast_id']);
        $bastIds = $basts->pluck('id')->all();

        if ($bastIds !== []) {
            $paths = array_merge(
                $paths,
                LhppBastImage::query()->whereIn('lhpp_bast_id', $bastIds)->pluck('file_path')->all(),
                LhppBastSignature::query()->whereIn('lhpp_bast_id', $bastIds)->pluck('signed_document_path')->all(),
            );

            $lpjRows = LpjPpl::query()->whereIn('lhpp_bast_id', $bastIds)->get([
                'lpj_document_path_termin1', 'ppl_document_path_termin1',
                'lpj_document_path_termin2', 'ppl_document_path_termin2',
            ]);
            foreach ($lpjRows as $lpj) {
                $paths = array_merge($paths, array_values($lpj->only([
                    'lpj_document_path_termin1', 'ppl_document_path_termin1',
                    'lpj_document_path_termin2', 'ppl_document_path_termin2',
                ])));
            }
        }

        $paths = array_merge(
            $paths,
            OrderDocument::query()->where('order_id', $orderId)->pluck('path_file')->all(),
        );

        $scope = OrderScopeOfWork::query()->where('order_id', $orderId)->first(['tanda_tangan']);
        if ($scope) {
            $paths[] = $scope->tanda_tangan;
        }

        $handover = WorkshopHandover::query()->where('order_id', $orderId)->first([
            'admin_signature_path', 'user_signature_path', 'photo_paths',
        ]);
        if ($handover) {
            $paths = array_merge($paths, [
                $handover->admin_signature_path,
                $handover->user_signature_path,
            ], $this->flattenPaths($handover->photo_paths));
        }

        // Keep the database sequence deterministic: deepest children first,
        // then each parent, with the Order record deleted last.
        $budgetVerificationIds = BudgetVerification::query()
            ->where('order_id', $orderId)
            ->when($hppIds !== [], fn ($query) => $query->orWhereIn('hpp_id', $hppIds))
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($orderId, $packageIds, $reportIds, $initialWorkIds, $hppIds, $budgetVerificationIds, $purchaseOrderIds, $bastIds, $taskIds): void {
            if ($packageIds !== []) {
                WorkshopWorkPackageAssignment::query()->whereIn('work_package_id', $packageIds)->delete();
                WorkshopWorkPackage::query()->whereIn('id', $packageIds)->delete();
            }

            if ($reportIds !== []) {
                QualityControlReportFile::query()->whereIn('quality_control_report_id', $reportIds)->delete();
                QualityControlSignature::query()->whereIn('quality_control_report_id', $reportIds)->delete();
                QualityControlReport::query()->whereIn('id', $reportIds)->delete();
            }

            if ($initialWorkIds !== []) {
                InitialWorkSignature::query()->whereIn('initial_work_id', $initialWorkIds)->delete();
                InitialWork::query()->whereIn('id', $initialWorkIds)->delete();
            }

            if ($bastIds !== []) {
                LpjPpl::query()->whereIn('lhpp_bast_id', $bastIds)->delete();
                LhppBastImage::query()->whereIn('lhpp_bast_id', $bastIds)->delete();
                LhppBastSignature::query()->whereIn('lhpp_bast_id', $bastIds)->delete();
                Garansi::query()->where(function ($query) use ($orderId, $bastIds): void {
                    $query->where('order_id', $orderId)->orWhereIn('lhpp_bast_id', $bastIds);
                })->delete();

                // Delete child Termin 2 rows before their Termin 1 parent.
                LhppBast::query()->whereIn('id', $bastIds)->orderByDesc('parent_lhpp_bast_id')->get()->each->delete();
            } else {
                Garansi::query()->where('order_id', $orderId)->delete();
            }

            if ($purchaseOrderIds !== []) {
                PurchaseOrder::query()->whereIn('id', $purchaseOrderIds)->delete();
            }

            if ($budgetVerificationIds !== []) {
                BudgetVerification::query()->whereIn('id', $budgetVerificationIds)->delete();
            }

            if ($hppIds !== []) {
                HppSignature::query()->whereIn('hpp_id', $hppIds)->delete();
                Hpp::query()->whereIn('id', $hppIds)->delete();
            }

            WorkshopHandover::query()->where('order_id', $orderId)->delete();
            OrderDocument::query()->where('order_id', $orderId)->delete();
            OrderScopeOfWork::query()->where('order_id', $orderId)->delete();

            if ($taskIds !== []) {
                BengkelTask::query()->whereIn('id', $taskIds)->delete();
            }

            OrderWorkshop::query()->where('order_id', $orderId)->delete();
            Order::query()->whereKey($orderId)->delete();
        });

        foreach ($paths as $path) {
            $this->deleteSafePath($path);
        }
    }

    /** @return list<string> */
    private function pathsFromPayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $paths = [];
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $paths = array_merge($paths, $this->pathsFromPayload($value));
            } elseif (is_string($value) && preg_match('/(path|file)/i', (string) $key)) {
                $paths[] = $value;
            }
        }

        return $paths;
    }

    /** @return list<string> */
    private function flattenPaths(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $paths = [];
        foreach ($value as $item) {
            $paths = array_merge($paths, $this->flattenPaths($item));
        }

        return $paths;
    }

    private function deleteSafePath(mixed $path): void
    {
        if (! is_string($path)) {
            return;
        }

        $path = trim($path);
        if ($path === '' || preg_match('/^(data:|https?:\/\/)/i', $path)
            || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)
            || in_array('..', explode('/', str_replace('\\', '/', $path)), true)) {
            return;
        }

        try {
            foreach ([Storage::disk('local'), Storage::disk('public')] as $disk) {
                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Order child file cleanup failed.', [
                'path' => basename($path),
                'exception' => $exception::class,
            ]);
        }
    }
}
