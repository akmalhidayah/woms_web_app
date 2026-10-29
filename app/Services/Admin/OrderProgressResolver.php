<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LpjPpl;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\User;
use App\Models\WorkshopHandover;
use App\Models\WorkshopWorkPackage;
use App\Services\BengkelTasks\WorkshopQualityControlQueue;
use App\Support\AdminMenuRegistry;
use App\Support\WorkshopReadiness;
use Illuminate\Support\Facades\Route;

final class OrderProgressResolver
{
    public function __construct(
        private readonly WorkshopReadiness $workshopReadiness,
        private readonly WorkshopQualityControlQueue $qualityControlQueue,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolve(Order $order, User $user): array
    {
        $type = $this->type($order);
        $paths = match ($type) {
            'Bengkel' => [$this->resolveWorkshop($order, $user)],
            'Gabungan' => [
                $this->resolveService($order, $user),
                $this->resolveWorkshop($order, $user),
            ],
            default => [$this->resolveService($order, $user)],
        };

        $preferredPath = $this->preferredPath($paths, $user);
        $completed = collect($paths)->every(fn (array $path): bool => $path['completed']);
        $status = $type === 'Gabungan'
            ? collect($paths)->map(fn (array $path): string => $path['label'].': '.$path['status'])->implode(' • ')
            : $preferredPath['status'];

        return [
            'type' => $type,
            'status' => $status,
            'tone' => $this->overallTone($paths, $completed),
            'action_label' => $completed ? 'Buka Detail Order' : $preferredPath['action_label'],
            'url' => $completed ? $this->orderUrl($order) : $preferredPath['url'],
            'paths' => collect($paths)
                ->map(fn (array $path): array => [
                    'label' => $path['label'],
                    'status' => $path['status'],
                    'tone' => $path['tone'],
                ])
                ->all(),
        ];
    }

    private function type(Order $order): string
    {
        return match ($order->catatan_status) {
            OrderUserNoteStatus::ApprovedWorkshop => 'Bengkel',
            OrderUserNoteStatus::ApprovedWorkshopJasa => 'Gabungan',
            default => 'Jasa',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveService(Order $order, User $user): array
    {
        if ($order->catatan_status === OrderUserNoteStatus::Reject) {
            return $this->serviceStage($order, $user, 'Order Ditolak', 'rose', 'Buka Detail Order');
        }

        if ($order->catatan_status === OrderUserNoteStatus::Pending) {
            return $this->serviceStage($order, $user, 'Order Menunggu Pemeriksaan', 'amber', 'Buka Detail Order');
        }

        $emergencyProgress = Order::priorityPrimaryFor($order->prioritas) === 'emergency'
            ? (int) ($order->initialWork?->progress_pekerjaan ?? 0)
            : 0;

        if ($emergencyProgress > 0 && $emergencyProgress < 100) {
            return $this->serviceStage(
                $order,
                $user,
                "Pekerjaan Berjalan {$emergencyProgress}% (Initial Work)",
                'blue',
                'Buka Detail Order',
            );
        }

        if (! $order->scopeOfWork) {
            return $this->serviceStage($order, $user, 'Order Perlu Dilengkapi', 'amber', 'Lengkapi Order');
        }

        $hpp = $order->latestHpp;

        if (! $hpp) {
            return $this->stage(
                $order,
                $user,
                'Jasa',
                'HPP Belum Dibuat',
                'blue',
                'Buat HPP',
                AdminMenuRegistry::MENU_CREATE_HPP,
                'admin.hpp.index',
                ['search' => $order->nomor_order],
            );
        }

        if ($hpp->status === Hpp::STATUS_DRAFT) {
            return $this->hppStage($order, $user, $hpp, 'HPP Masih Draft', 'amber', 'Lanjutkan HPP');
        }

        if ($hpp->status === Hpp::STATUS_REJECTED) {
            return $this->hppStage($order, $user, $hpp, 'HPP Ditolak', 'rose', 'Periksa HPP');
        }

        if ($hpp->status === Hpp::STATUS_IN_REVIEW) {
            return $this->hppStage($order, $user, $hpp, 'HPP Dalam Proses Approval', 'violet', 'Buka HPP');
        }

        if ($hpp->status !== Hpp::STATUS_APPROVED) {
            return $this->serviceStage($order, $user, 'Perlu Pemeriksaan', 'slate', 'Buka Detail Order');
        }

        $budget = $order->budgetVerification;

        if (! $budget) {
            return $this->budgetStage($order, $user, 'Menunggu Verifikasi Anggaran', 'amber');
        }

        if ($budget->status_anggaran === BudgetVerification::STATUS_UNAVAILABLE) {
            return $this->budgetStage($order, $user, 'Anggaran Tidak Tersedia', 'rose');
        }

        if (! $budget->isReadyForPurchaseOrder()) {
            $label = $budget->status_anggaran === BudgetVerification::STATUS_WAITING
                ? 'Menunggu Anggaran'
                : 'Verifikasi Anggaran Belum Lengkap';

            return $this->budgetStage($order, $user, $label, 'amber');
        }

        $purchaseOrder = $order->purchaseOrder;

        if (! $purchaseOrder || blank($purchaseOrder->purchase_order_number) || ! $purchaseOrder->approve_manager) {
            return $this->purchaseOrderStage($order, $user, 'Menunggu Purchase Order', 'amber', 'Lengkapi Purchase Order');
        }

        if ($purchaseOrder->target_penyelesaian !== null && $purchaseOrder->approval_target !== 'setuju') {
            return $this->purchaseOrderStage(
                $order,
                $user,
                $purchaseOrder->approval_target === 'tidak_setuju' ? 'Estimasi Pekerjaan Ditolak' : 'Menunggu Persetujuan Estimasi',
                $purchaseOrder->approval_target === 'tidak_setuju' ? 'rose' : 'amber',
                'Tinjau Estimasi',
                ['tab' => 'estimate_approval', 'search' => $order->nomor_order],
            );
        }

        $workProgress = Order::priorityPrimaryFor($order->prioritas) === 'emergency'
            ? max($emergencyProgress, (int) ($purchaseOrder->progress_pekerjaan ?? 0))
            : (int) ($purchaseOrder->progress_pekerjaan ?? 0);

        if ($workProgress < 100) {
            return $this->purchaseOrderStage(
                $order,
                $user,
                $workProgress > 0 ? "Pekerjaan Berjalan {$workProgress}%" : 'Pekerjaan Siap Dimulai',
                $workProgress > 0 ? 'blue' : 'slate',
                'Buka Progress Pekerjaan',
                ['tab' => $workProgress > 0 ? 'in_progress' : 'ready', 'search' => $order->nomor_order],
            );
        }

        $terminOne = $order->lhppBasts->firstWhere('termin_type', 'termin_1');
        $garansi = $terminOne?->garansi ?: $order->garansi;

        if (! $garansi || $garansi->garansi_months === null) {
            return $this->stage(
                $order,
                $user,
                'Jasa',
                'Menunggu Penetapan Garansi',
                'amber',
                'Set Garansi',
                AdminMenuRegistry::MENU_GARANSI,
                'admin.garansi.index',
                ['search' => $order->nomor_order],
            );
        }

        if (! $terminOne) {
            return $this->bastStage($order, $user, 'BAST Belum Dibuat', 'amber', 'Buka BAST');
        }

        if ($terminOne->quality_control_status === 'rejected' || $terminOne->approval_status === LhppBast::APPROVAL_REJECTED) {
            return $this->bastStage($order, $user, 'BAST Ditolak', 'rose', 'Periksa BAST');
        }

        if ($terminOne->quality_control_status !== 'approved') {
            return $this->bastStage($order, $user, 'BAST Menunggu Pemeriksaan', 'amber', 'Periksa BAST');
        }

        if ($terminOne->approval_status === LhppBast::APPROVAL_IN_REVIEW) {
            return $this->bastStage($order, $user, 'BAST Dalam Proses Approval', 'violet', 'Buka BAST');
        }

        if ($terminOne->approval_status !== LhppBast::APPROVAL_APPROVED) {
            return $this->bastStage($order, $user, 'Perlu Pemeriksaan', 'slate', 'Buka BAST');
        }

        if (! $this->documentsComplete($terminOne->lpjPpl, 1)) {
            return $this->lpjStage($order, $user, 'LPJ/PPL Belum Lengkap', 'amber');
        }

        if ($terminOne->termin1_status !== 'sudah') {
            return $this->lpjStage($order, $user, 'Menunggu Penyelesaian Termin 1', 'blue');
        }

        if ((int) $garansi->garansi_months === 0) {
            return $this->serviceStage($order, $user, 'Pekerjaan Selesai', 'emerald', 'Buka Detail Order', true);
        }

        $terminTwo = $order->lhppBasts->firstWhere('termin_type', 'termin_2');

        if (! $terminTwo) {
            return $this->bastStage($order, $user, 'Menunggu BAST Termin 2', 'amber', 'Buka BAST');
        }

        if ($terminTwo->approval_status === LhppBast::APPROVAL_REJECTED) {
            return $this->bastStage($order, $user, 'BAST Termin 2 Ditolak', 'rose', 'Periksa BAST');
        }

        if ($terminTwo->approval_status === LhppBast::APPROVAL_IN_REVIEW) {
            return $this->bastStage($order, $user, 'BAST Termin 2 Dalam Approval', 'violet', 'Buka BAST');
        }

        if ($terminTwo->approval_status !== LhppBast::APPROVAL_APPROVED) {
            return $this->bastStage($order, $user, 'Perlu Pemeriksaan', 'slate', 'Buka BAST');
        }

        if (! $this->documentsComplete($terminOne->lpjPpl, 2)) {
            return $this->lpjStage($order, $user, 'LPJ/PPL Termin 2 Belum Lengkap', 'amber');
        }

        if ($terminOne->termin2_status !== 'sudah') {
            return $this->lpjStage($order, $user, 'Menunggu Penyelesaian Termin 2', 'blue');
        }

        return $this->serviceStage($order, $user, 'Pekerjaan Selesai', 'emerald', 'Buka Detail Order', true);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveWorkshop(Order $order, User $user): array
    {
        $workshop = $order->orderWorkshop;

        if (! $workshop) {
            return $this->workshopStage($order, $user, 'Data Pekerjaan Bengkel Belum Lengkap', 'amber', 'Buka Order Bengkel');
        }

        $readiness = $this->workshopReadiness->resolve($workshop);

        if (! $readiness['can_advance']) {
            return $this->workshopStage($order, $user, $readiness['label'], 'amber', 'Buka Persiapan Bengkel');
        }

        $packages = $order->workPackages;
        $pendingPackage = $packages->firstWhere('status', WorkshopWorkPackage::STATUS_PENDING);
        $incompletePackages = $packages->where('status', '!=', WorkshopWorkPackage::STATUS_COMPLETED);

        if ($pendingPackage) {
            return $this->workPackageStage($order, $user, 'Work Package Pending', 'orange');
        }

        if ($packages->isNotEmpty() && $incompletePackages->isNotEmpty()) {
            $completed = $packages->count() - $incompletePackages->count();

            return $this->workPackageStage(
                $order,
                $user,
                "Work Package {$completed}/{$packages->count()} Selesai",
                'blue',
            );
        }

        if ($workshop->progress_status === OrderWorkshop::PROGRESS_QUALITY_CONTROL) {
            $qcStatus = $this->qualityControlQueue->status($order);

            if ($qcStatus['key'] === 'completed') {
                if ($order->workshopHandover?->status === WorkshopHandover::STATUS_WAITING_USER_SIGNATURE) {
                    return $this->handoverStage($order, $user, 'Menunggu Tanda Tangan Serah Terima', 'violet');
                }

                if ($order->workshopHandover?->isCompleted()) {
                    return $this->workshopStage($order, $user, 'Pekerjaan Selesai', 'emerald', 'Buka Detail Order', true);
                }

                return $this->handoverStage($order, $user, 'Menunggu Serah Terima Bengkel', 'amber');
            }

            return $this->stage(
                $order,
                $user,
                'Bengkel',
                'Quality Control: '.$qcStatus['label'],
                (string) $qcStatus['tone'],
                $qcStatus['key'] === 'approval' ? 'Buka Proses QC' : 'Buka Quality Control',
                AdminMenuRegistry::MENU_QUALITY_CONTROL_BENGKEL,
                'admin.workshop-quality-control.index',
                ['search' => $order->nomor_order],
            );
        }

        if ($order->workshopHandover?->status === WorkshopHandover::STATUS_WAITING_USER_SIGNATURE) {
            return $this->handoverStage($order, $user, 'Menunggu Tanda Tangan Serah Terima', 'violet');
        }

        if ($order->workshopHandover?->isCompleted() || $workshop->legacyCompleted()) {
            return $this->workshopStage($order, $user, 'Pekerjaan Selesai', 'emerald', 'Buka Detail Order', true);
        }

        if ($workshop->progress_status === OrderWorkshop::PROGRESS_DONE) {
            return $this->handoverStage($order, $user, 'Menunggu Serah Terima Bengkel', 'amber');
        }

        if ($workshop->progress_status === OrderWorkshop::PROGRESS_PENDING) {
            return $this->workshopStage($order, $user, 'Pekerjaan Bengkel Pending', 'orange', 'Buka Order Bengkel');
        }

        if (in_array($workshop->progress_status, [
            OrderWorkshop::PROGRESS_MENUNGGU_JADWAL,
            OrderWorkshop::PROGRESS_IN_PROGRESS,
        ], true)) {
            return $this->workshopStage(
                $order,
                $user,
                OrderWorkshop::progressOptions()[$workshop->progress_status] ?? 'Pekerjaan Bengkel Berjalan',
                $workshop->progress_status === OrderWorkshop::PROGRESS_IN_PROGRESS ? 'blue' : 'amber',
                'Buka Order Bengkel',
            );
        }

        return $this->workshopStage($order, $user, 'Perlu Pemeriksaan', 'slate', 'Buka Order Bengkel');
    }

    /**
     * @param  list<array<string, mixed>>  $paths
     * @return array<string, mixed>
     */
    private function preferredPath(array $paths, User $user): array
    {
        $accessible = collect($paths)->filter(function (array $path) use ($user): bool {
            return $path['label'] === 'Jasa'
                ? AdminMenuRegistry::canAccess($user, AdminMenuRegistry::MENU_ORDER_JASA)
                : AdminMenuRegistry::canAccess($user, AdminMenuRegistry::MENU_ORDER_BENGKEL);
        });

        return $accessible->firstWhere('completed', false)
            ?? $accessible->first()
            ?? collect($paths)->firstWhere('completed', false)
            ?? $paths[0];
    }

    /**
     * @param  list<array<string, mixed>>  $paths
     */
    private function overallTone(array $paths, bool $completed): string
    {
        if ($completed) {
            return 'emerald';
        }

        $tones = collect($paths)->pluck('tone');

        return match (true) {
            $tones->contains('rose') => 'rose',
            $tones->contains('orange') => 'orange',
            $tones->contains('violet') => 'violet',
            $tones->contains('amber') => 'amber',
            $tones->contains('blue') => 'blue',
            default => 'slate',
        };
    }

    private function documentsComplete(?LpjPpl $lpjPpl, int $termin): bool
    {
        if (! $lpjPpl) {
            return false;
        }

        $suffix = "termin{$termin}";

        return collect([
            "lpj_number_{$suffix}",
            "ppl_number_{$suffix}",
            "lpj_document_path_{$suffix}",
            "ppl_document_path_{$suffix}",
        ])->every(fn (string $field): bool => filled($lpjPpl->{$field}));
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceStage(
        Order $order,
        User $user,
        string $status,
        string $tone,
        string $actionLabel,
        bool $completed = false,
    ): array {
        return $this->stage(
            $order,
            $user,
            'Jasa',
            $status,
            $tone,
            $actionLabel,
            AdminMenuRegistry::MENU_ORDER_JASA,
            'admin.orders.show',
            ['order' => $order],
            $completed,
        );
    }

    /** @return array<string, mixed> */
    private function hppStage(Order $order, User $user, Hpp $hpp, string $status, string $tone, string $actionLabel): array
    {
        return $this->stage(
            $order,
            $user,
            'Jasa',
            $status,
            $tone,
            $actionLabel,
            AdminMenuRegistry::MENU_CREATE_HPP,
            'admin.hpp.edit.by-id',
            ['hpp' => $hpp->getKey()],
        );
    }

    /** @return array<string, mixed> */
    private function budgetStage(Order $order, User $user, string $status, string $tone): array
    {
        return $this->stage(
            $order,
            $user,
            'Jasa',
            $status,
            $tone,
            'Buka Verifikasi Anggaran',
            AdminMenuRegistry::MENU_VERIFIKASI_ANGGARAN,
            'admin.budget-verification.index',
            ['search' => $order->nomor_order],
        );
    }

    /** @return array<string, mixed> */
    private function purchaseOrderStage(
        Order $order,
        User $user,
        string $status,
        string $tone,
        string $actionLabel,
        ?array $parameters = null,
    ): array {
        return $this->stage(
            $order,
            $user,
            'Jasa',
            $status,
            $tone,
            $actionLabel,
            AdminMenuRegistry::MENU_PURCHASE_ORDER,
            'admin.purchase-order.index',
            $parameters ?? ['search' => $order->nomor_order],
        );
    }

    /** @return array<string, mixed> */
    private function bastStage(Order $order, User $user, string $status, string $tone, string $actionLabel): array
    {
        return $this->stage(
            $order,
            $user,
            'Jasa',
            $status,
            $tone,
            $actionLabel,
            AdminMenuRegistry::MENU_LHPP_BAST,
            'admin.lhpp.index',
            ['search' => $order->nomor_order],
        );
    }

    /** @return array<string, mixed> */
    private function lpjStage(Order $order, User $user, string $status, string $tone): array
    {
        return $this->stage(
            $order,
            $user,
            'Jasa',
            $status,
            $tone,
            'Buka LPJ/PPL',
            AdminMenuRegistry::MENU_LPJ_PPL,
            'admin.lpj.index',
            ['search' => $order->nomor_order],
        );
    }

    /** @return array<string, mixed> */
    private function workshopStage(
        Order $order,
        User $user,
        string $status,
        string $tone,
        string $actionLabel,
        bool $completed = false,
    ): array {
        return $this->stage(
            $order,
            $user,
            'Bengkel',
            $status,
            $tone,
            $actionLabel,
            AdminMenuRegistry::MENU_ORDER_BENGKEL,
            $completed ? 'admin.orders.show' : 'admin.orders.workshop.index',
            $completed ? ['order' => $order] : ['search' => $order->nomor_order],
            $completed,
        );
    }

    /** @return array<string, mixed> */
    private function workPackageStage(Order $order, User $user, string $status, string $tone): array
    {
        return $this->stage(
            $order,
            $user,
            'Bengkel',
            $status,
            $tone,
            'Buka Work Package',
            AdminMenuRegistry::MENU_ORDER_BENGKEL,
            'admin.orders.workshop.work-packages.index',
            ['order' => $order],
        );
    }

    /** @return array<string, mixed> */
    private function handoverStage(Order $order, User $user, string $status, string $tone): array
    {
        return $this->stage(
            $order,
            $user,
            'Bengkel',
            $status,
            $tone,
            'Buka Serah Terima',
            AdminMenuRegistry::MENU_SERAH_TERIMA_BENGKEL,
            'admin.workshop-handover.index',
            ['search' => $order->nomor_order],
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function stage(
        Order $order,
        User $user,
        string $label,
        string $status,
        string $tone,
        string $actionLabel,
        string $menuKey,
        string $routeName,
        array $parameters,
        bool $completed = false,
    ): array {
        $canOpenDestination = AdminMenuRegistry::canAccess($user, $menuKey) && Route::has($routeName);

        return [
            'label' => $label,
            'status' => $status,
            'tone' => $tone,
            'action_label' => $canOpenDestination ? $actionLabel : 'Buka Detail Order',
            'url' => $canOpenDestination ? route($routeName, $parameters) : $this->orderUrl($order),
            'completed' => $completed,
        ];
    }

    private function orderUrl(Order $order): string
    {
        return route('admin.orders.show', ['order' => $order]);
    }
}
