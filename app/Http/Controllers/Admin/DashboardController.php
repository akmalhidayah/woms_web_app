<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Http\Controllers\Controller;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementTarget;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $documentOnProcessHPPAmount = $this->sumPendingHppApprovalAmount();
        $approvalProcessHPPAmount = $this->sumApprovedHppsWaitingForPoAmount();
        $documentOnProcessPOAmount = $this->sumPurchaseOrdersWithNumberAndDocumentAmount();
        $documentPRPOAmount = $this->sumNormalLhppBastAmount();
        $urgentAmount = $this->sumEmergencyLhppBastAmount();
        $totalAmount1 = $documentOnProcessHPPAmount + $approvalProcessHPPAmount + $documentOnProcessPOAmount;
        $totalAmount2 = $documentPRPOAmount + $urgentAmount;
        $totalSeluruhAmount = $totalAmount1 + $totalAmount2;
        $totalKuotaKontrak = $this->sumActiveOutlineAgreementBudget();
        $totalBiayaPemeliharaan = $this->sumActiveOutlineAgreementMaintenanceTargets();
        $totalJasaPemeliharaan = $this->sumVerifiedMaintenanceServiceAmount();

        return view('dashboards.admin', [
            'outstandingNotifications' => $this->countOutstandingOrders(),
            'pendingProcessJasa' => $this->countPendingHppApprovals(),
            'approvalProcessHPPCount' => $this->countApprovedHppsWaitingForPo(),
            'documentOnProcessPOCount' => $this->countPurchaseOrdersWithNumberAndDocument(),
            'documentOnProcessHPPAmount' => $documentOnProcessHPPAmount,
            'approvalProcessHPPAmount' => $approvalProcessHPPAmount,
            'documentOnProcessPOAmount' => $documentOnProcessPOAmount,
            'documentPRPOAmount' => $documentPRPOAmount,
            'urgentAmount' => $urgentAmount,
            'totalAmount1' => $totalAmount1,
            'totalAmount2' => $totalAmount2,
            'totalRealisasiBiaya' => $totalAmount2,
            'totalSeluruhAmount' => $totalSeluruhAmount,
            'totalKuotaKontrak' => $totalKuotaKontrak,
            'sisaKuotaKontrak' => $totalKuotaKontrak - $totalSeluruhAmount,
            'targetPemeliharaan' => $totalBiayaPemeliharaan,
            'totalJasaPemeliharaan' => $totalJasaPemeliharaan,
            'sisaBiayaPemeliharaan' => $totalBiayaPemeliharaan - $totalJasaPemeliharaan,
            'periodeKontrak' => $this->resolveActiveOutlineAgreementPeriod(),
            'realizationYears' => $this->realizationYearsList(),
            'realizationChartData' => $this->buildRealizationChartData(),
        ]);
    }

    public function years(): JsonResponse
    {
        return response()->json($this->realizationYearsList());
    }

    public function realizationChart(Request $request): JsonResponse
    {
        return response()->json($this->buildRealizationChartData(
            $request->integer('startYear') ?: null,
            $request->integer('endYear') ?: null,
            $request->integer('startMonth') ?: null,
            $request->integer('endMonth') ?: null,
        ));
    }

    private function countOutstandingOrders(): int
    {
        return Order::query()
            ->whereHas('documents', fn (Builder $query) => $query->where('jenis_dokumen', OrderDocumentType::Abnormalitas->value))
            ->whereHas('documents', fn (Builder $query) => $query->where('jenis_dokumen', OrderDocumentType::GambarTeknik->value))
            ->whereHas('scopeOfWork')
            ->doesntHave('hpps')
            ->count();
    }

    private function countPendingHppApprovals(): int
    {
        return Hpp::query()
            ->where('status', Hpp::STATUS_IN_REVIEW)
            ->whereNotNull('submitted_at')
            ->count();
    }

    private function countApprovedHppsWaitingForPo(): int
    {
        return Hpp::query()
            ->where('status', Hpp::STATUS_APPROVED)
            ->whereDoesntHave('purchaseOrder', fn (Builder $query) => $query
                ->whereNotNull('purchase_order_number')
                ->whereRaw("TRIM(purchase_order_number) <> ''"))
            ->count();
    }

    private function countPurchaseOrdersWithNumberAndDocument(): int
    {
        return PurchaseOrder::query()
            ->whereNotNull('purchase_order_number')
            ->whereRaw("TRIM(purchase_order_number) <> ''")
            ->whereNotNull('po_document_path')
            ->whereRaw("TRIM(po_document_path) <> ''")
            ->count();
    }

    private function sumPendingHppApprovalAmount(): int
    {
        return $this->moneyInt(Hpp::query()
            ->where('status', Hpp::STATUS_IN_REVIEW)
            ->whereNotNull('submitted_at')
            ->sum('total_keseluruhan'));
    }

    private function sumApprovedHppsWaitingForPoAmount(): int
    {
        return $this->moneyInt(Hpp::query()
            ->where('status', Hpp::STATUS_APPROVED)
            ->whereDoesntHave('purchaseOrder', fn (Builder $query) => $query
                ->whereNotNull('purchase_order_number')
                ->whereRaw("TRIM(purchase_order_number) <> ''")
                ->whereNotNull('po_document_path')
                ->whereRaw("TRIM(po_document_path) <> ''"))
            ->sum('total_keseluruhan'));
    }

    private function sumPurchaseOrdersWithNumberAndDocumentAmount(): int
    {
        return $this->moneyInt(Hpp::query()
            ->whereHas('purchaseOrder', fn (Builder $query) => $query
                ->whereNotNull('purchase_order_number')
                ->whereRaw("TRIM(purchase_order_number) <> ''")
                ->whereNotNull('po_document_path')
                ->whereRaw("TRIM(po_document_path) <> ''"))
            ->sum('total_keseluruhan'));
    }

    private function sumNormalLhppBastAmount(): int
    {
        return $this->moneyInt($this->baseLhppBastRealizationQuery()
            ->whereHas('order', fn (Builder $query) => $query->whereNotIn('prioritas', $this->emergencyPriorities()))
            ->whereNotNull('purchase_order_number')
            ->whereRaw("TRIM(purchase_order_number) <> ''")
            ->sum('total_aktual_biaya'));
    }

    private function sumEmergencyLhppBastAmount(): int
    {
        return $this->moneyInt($this->baseLhppBastRealizationQuery()
            ->whereHas('order', fn (Builder $query) => $query->whereIn('prioritas', $this->emergencyPriorities()))
            ->sum('total_aktual_biaya'));
    }

    private function baseLhppBastRealizationQuery(): Builder
    {
        return LhppBast::query()
            ->where('termin_type', 'termin_1')
            ->whereNull('parent_lhpp_bast_id');
    }

    /**
     * @return list<string>
     */
    private function emergencyPriorities(): array
    {
        return [
            Order::PRIORITY_URGENT,
            Order::PRIORITY_HIGH,
        ];
    }

    private function sumActiveOutlineAgreementBudget(): int
    {
        return $this->moneyInt(OutlineAgreement::query()
            ->where('status', OutlineAgreement::STATUS_ACTIVE)
            ->sum('current_total_nilai'));
    }

    private function sumActiveOutlineAgreementMaintenanceTargets(): int
    {
        return $this->moneyInt(OutlineAgreementTarget::query()
            ->whereHas('outlineAgreement', fn (Builder $query) => $query->where('status', OutlineAgreement::STATUS_ACTIVE))
            ->sum('nilai_target'));
    }

    private function sumVerifiedMaintenanceServiceAmount(): int
    {
        return $this->moneyInt(Hpp::query()
            ->whereHas('budgetVerification', fn (Builder $query) => $query
                ->where('status_anggaran', 'Tersedia')
                ->where('kategori_item', 'jasa')
                ->where('kategori_biaya', 'pemeliharaan'))
            ->sum('total_keseluruhan'));
    }

    /**
     * @return array{start: string|null, end: string|null, adendum: string|null}
     */
    private function resolveActiveOutlineAgreementPeriod(): array
    {
        $start = OutlineAgreement::query()
            ->where('status', OutlineAgreement::STATUS_ACTIVE)
            ->min('current_period_start');
        $end = OutlineAgreement::query()
            ->where('status', OutlineAgreement::STATUS_ACTIVE)
            ->max('current_period_end');

        return [
            'start' => $start ?: null,
            'end' => $end ?: null,
            'adendum' => null,
        ];
    }

    /**
     * @return list<int>
     */
    private function realizationYearsList(): array
    {
        return $this->baseLhppBastRealizationQuery()
            ->whereNotNull('tanggal_bast')
            ->pluck('tanggal_bast')
            ->map(fn ($date): int => Carbon::parse($date)->year)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return list<array{year: int, month: int, label: string, total: int, normal_total: int, urgent_total: int}>
     */
    private function buildRealizationChartData(
        ?int $startYear = null,
        ?int $endYear = null,
        ?int $startMonth = null,
        ?int $endMonth = null,
    ): array {
        $availableYears = $this->realizationYearsList();
        $startYear ??= $availableYears[0] ?? (int) Carbon::now()->year;
        $endYear ??= $availableYears[array_key_last($availableYears)] ?? $startYear;
        $startMonth = $this->normalizeMonth($startMonth) ?? 1;
        $endMonth = $this->normalizeMonth($endMonth) ?? 12;

        if ($startYear > $endYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
        }

        $startDate = Carbon::create($startYear, $startMonth, 1)->startOfDay();
        $endDate = Carbon::create($endYear, $endMonth, 1)->endOfMonth();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfMonth(), $startDate->copy()->endOfMonth()];
        }

        $rows = $this->baseLhppBastRealizationQuery()
            ->with('order:id,prioritas')
            ->whereBetween('tanggal_bast', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('tanggal_bast')
            ->get(['id', 'order_id', 'tanggal_bast', 'total_aktual_biaya']);

        return $rows
            ->groupBy(fn (LhppBast $row): string => $row->tanggal_bast?->format('Y-m') ?? 'unknown')
            ->filter(fn ($group, string $key): bool => $key !== 'unknown')
            ->map(function ($group, string $key): array {
                [$year, $month] = array_map('intval', explode('-', $key));
                $normalTotal = $group
                    ->reject(fn (LhppBast $row): bool => in_array($row->order?->prioritas, $this->emergencyPriorities(), true))
                    ->sum(fn (LhppBast $row): float => (float) $row->total_aktual_biaya);
                $urgentTotal = $group
                    ->filter(fn (LhppBast $row): bool => in_array($row->order?->prioritas, $this->emergencyPriorities(), true))
                    ->sum(fn (LhppBast $row): float => (float) $row->total_aktual_biaya);
                $normalTotal = $this->moneyInt($normalTotal);
                $urgentTotal = $this->moneyInt($urgentTotal);

                return [
                    'year' => $year,
                    'month' => $month,
                    'label' => Carbon::create($year, $month, 1)->translatedFormat('M Y'),
                    'total' => $normalTotal + $urgentTotal,
                    'normal_total' => $normalTotal,
                    'urgent_total' => $urgentTotal,
                ];
            })
            ->sortBy([['year', 'asc'], ['month', 'asc']])
            ->values()
            ->all();
    }

    private function normalizeMonth(?int $month): ?int
    {
        if ($month === null || $month < 1 || $month > 12) {
            return null;
        }

        return $month;
    }

    private function moneyInt(mixed $value): int
    {
        return (int) round((float) $value);
    }
}
