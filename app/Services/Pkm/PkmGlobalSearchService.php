<?php

declare(strict_types=1);

namespace App\Services\Pkm;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\Order;
use App\Support\BastIndexTabs;
use App\Support\HppIndexTabs;
use App\Support\PkmJobWaitingQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Pencarian umum panel PKM. Read-only.
 *
 * Satu order dapat tampil di beberapa menu PKM (Buat HPP, List Pekerjaan, Buat BAST/LHPP, Dokumen).
 * Posisi tiap menu memakai predikat yang sama dengan halaman daftarnya agar hasil pencarian konsisten.
 */
final class PkmGlobalSearchService
{
    public const RESULT_LIMIT = 5;

    private const CANDIDATE_LIMIT = 15;

    public const MENU_HPP = 'Buat HPP';

    public const MENU_JOB_WAITING = 'List Pekerjaan';

    public const MENU_BAST = 'Buat BAST/LHPP';

    public const MENU_DOCUMENTS = 'Dokumen';

    public function __construct(
        private readonly PkmDocumentQueryService $documentQuery,
        private readonly BastIndexTabs $bastTabs,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $search): array
    {
        $search = preg_replace('/\s+/u', ' ', trim($search)) ?? '';

        if (mb_strlen($search) < 2 || mb_strlen($search) > 100) {
            return [];
        }

        $prefix = $this->escapeLike($search).'%';
        $keywords = collect(preg_split('/\s+/u', $search) ?: [])
            ->map(fn (string $keyword): string => $this->escapeLike($keyword))
            ->filter(fn (string $keyword): bool => $keyword !== '')
            ->take(6)
            ->values()
            ->all();

        $orders = Order::query()
            ->select(['id', 'nomor_order', 'notifikasi', 'nama_pekerjaan'])
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->where(function (Builder $query) use ($search, $prefix, $keywords): void {
                $query
                    ->where('nomor_order', $search)
                    ->orWhere('notifikasi', $search)
                    ->orWhere('nomor_order', 'like', $prefix)
                    ->orWhere('notifikasi', 'like', $prefix)
                    ->orWhere(function (Builder $nameQuery) use ($keywords): void {
                        foreach ($keywords as $keyword) {
                            $nameQuery->where('nama_pekerjaan', 'like', "%{$keyword}%");
                        }
                    });
            })
            ->orderByRaw(
                'CASE '
                .'WHEN nomor_order = ? THEN 0 '
                .'WHEN notifikasi = ? THEN 1 '
                .'WHEN nomor_order LIKE ? THEN 2 '
                .'WHEN notifikasi LIKE ? THEN 3 '
                .'ELSE 4 END',
                [$search, $search, $prefix, $prefix],
            )
            ->orderByDesc('id')
            ->limit(self::CANDIDATE_LIMIT)
            ->with($this->relations())
            ->get();

        if ($orders->isEmpty()) {
            return [];
        }

        $ids = $orders->modelKeys();
        $jobWaitingIds = $this->idSet(PkmJobWaitingQuery::apply(Order::query()->whereIn('id', $ids)));
        $documentIds = $this->idSet($this->documentQuery->applyBaseScope(Order::query()->whereIn('id', $ids)));
        $completeDocumentIds = $this->idSet($this->documentQuery->applyStatusFilter(
            $this->documentQuery->applyBaseScope(Order::query()->whereIn('id', $ids)),
            'complete',
        ));
        $bastReadyIds = $this->idSet($this->bastReadyQuery($ids));

        return $orders
            ->map(function (Order $order) use ($jobWaitingIds, $documentIds, $completeDocumentIds, $bastReadyIds): ?array {
                $locations = $this->locations($order, $jobWaitingIds, $documentIds, $completeDocumentIds, $bastReadyIds);

                if ($locations === []) {
                    return null;
                }

                $primary = collect($locations)->firstWhere('active', true) ?? end($locations);

                return [
                    'id' => (int) $order->getKey(),
                    'order_number' => (string) $order->nomor_order,
                    'notification_number' => filled($order->notifikasi) ? (string) $order->notifikasi : null,
                    'job_name' => (string) $order->nama_pekerjaan,
                    'menu' => $primary['menu'],
                    'status' => $primary['status'],
                    'tone' => $primary['tone'],
                    'action_label' => 'Buka '.$primary['menu'],
                    'url' => $primary['url'],
                    'locations' => $locations,
                ];
            })
            ->filter()
            ->take(self::RESULT_LIMIT)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, true>  $jobWaitingIds
     * @param  array<int, true>  $documentIds
     * @param  array<int, true>  $completeDocumentIds
     * @param  array<int, true>  $bastReadyIds
     * @return list<array{menu: string, status: string, tone: string, url: string, active: bool}>
     */
    private function locations(Order $order, array $jobWaitingIds, array $documentIds, array $completeDocumentIds, array $bastReadyIds): array
    {
        $number = (string) $order->nomor_order;
        $locations = [];

        // Buat HPP
        $hpp = $order->latestHpp;
        if ($hpp) {
            $tab = $this->hppTab($hpp);
            $locations[] = [
                'menu' => self::MENU_HPP,
                'status' => 'HPP '.mb_strtolower((string) (Hpp::statusOptions()[$hpp->status] ?? $hpp->status)),
                'tone' => match ($hpp->status) {
                    Hpp::STATUS_APPROVED => 'emerald',
                    Hpp::STATUS_REJECTED => 'rose',
                    Hpp::STATUS_IN_REVIEW => 'violet',
                    default => 'amber',
                },
                'url' => route('pkm.hpp.index', ['tab' => $tab, 'search' => $number]),
                'active' => $hpp->status !== Hpp::STATUS_APPROVED,
            ];
        } elseif ($order->scopeOfWork) {
            $locations[] = [
                'menu' => self::MENU_HPP,
                'status' => 'HPP belum dibuat',
                'tone' => 'amber',
                'url' => route('pkm.hpp.index'),
                'active' => true,
            ];
        }

        // List Pekerjaan
        if (isset($jobWaitingIds[$order->getKey()])) {
            $purchaseOrder = $order->purchaseOrder;
            $usesPurchaseOrder = $purchaseOrder && $purchaseOrder->approve_manager && filled($purchaseOrder->purchase_order_number);
            $progress = (int) (($usesPurchaseOrder ? $purchaseOrder->progress_pekerjaan : $order->initialWork?->progress_pekerjaan) ?? 0);
            $locations[] = [
                'menu' => self::MENU_JOB_WAITING,
                'status' => $progress >= 100 ? 'Pekerjaan selesai (100%)' : "Progress pekerjaan {$progress}%",
                'tone' => $progress >= 100 ? 'emerald' : 'blue',
                'url' => route('pkm.jobwaiting', ['search' => $number]),
                'active' => true,
            ];
        }

        // Buat BAST/LHPP
        $terminOne = $order->lhppBasts->firstWhere('termin_type', 'termin_1');
        if ($terminOne) {
            $tab = $this->bastTab($terminOne);
            $locations[] = [
                'menu' => self::MENU_BAST,
                'status' => 'BAST Termin 1: '.($this->bastTabs->options(BastIndexTabs::CONTEXT_PKM)[$tab] ?? $tab),
                'tone' => match ($tab) {
                    BastIndexTabs::TAB_APPROVED, BastIndexTabs::TAB_HISTORY => 'emerald',
                    BastIndexTabs::TAB_IN_PROGRESS => 'violet',
                    default => 'amber',
                },
                'url' => route('pkm.lhpp.index', ['tab' => $tab, 'search' => $number]),
                'active' => ! in_array($tab, [BastIndexTabs::TAB_APPROVED, BastIndexTabs::TAB_HISTORY], true),
            ];
        } elseif (isset($bastReadyIds[$order->getKey()])) {
            $locations[] = [
                'menu' => self::MENU_BAST,
                'status' => 'Siap dibuat BAST Termin 1',
                'tone' => 'amber',
                'url' => route('pkm.lhpp.index'),
                'active' => true,
            ];
        }

        // Dokumen
        if (isset($documentIds[$order->getKey()])) {
            $complete = isset($completeDocumentIds[$order->getKey()]);
            $locations[] = [
                'menu' => self::MENU_DOCUMENTS,
                'status' => $complete ? 'Dokumen lengkap' : 'Dokumen belum lengkap',
                'tone' => $complete ? 'emerald' : 'amber',
                'url' => route('pkm.laporan', ['notification_number' => $number]),
                'active' => ! $complete,
            ];
        }

        return $locations;
    }

    private function hppTab(Hpp $hpp): string
    {
        foreach (array_keys(HppIndexTabs::options()) as $tab) {
            if (HppIndexTabs::apply(Hpp::query()->whereKey($hpp->getKey()), $tab)->exists()) {
                return $tab;
            }
        }

        return HppIndexTabs::ACTION;
    }

    private function bastTab(LhppBast $bast): string
    {
        foreach (array_keys($this->bastTabs->options(BastIndexTabs::CONTEXT_PKM)) as $tab) {
            $query = LhppBast::query()->where('termin_type', 'termin_1')->whereKey($bast->getKey());

            if ($this->bastTabs->apply($query, $tab, BastIndexTabs::CONTEXT_PKM)->exists()) {
                return $tab;
            }
        }

        return BastIndexTabs::TAB_ACTION;
    }

    /**
     * Predikat yang sama dengan Pkm\LhppController::eligibleOrders() (order siap dibuat BAST Termin 1).
     *
     * @param  list<int|string>  $ids
     * @return Builder<Order>
     */
    private function bastReadyQuery(array $ids): Builder
    {
        return Order::query()
            ->whereIn('id', $ids)
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('purchaseOrder', fn (Builder $purchaseOrder): Builder => $purchaseOrder
                        ->whereNotNull('purchase_order_number')
                        ->whereRaw("TRIM(purchase_order_number) <> ''")
                        ->where('progress_pekerjaan', 100))
                    ->orWhereHas('initialWork', fn (Builder $initialWork): Builder => $initialWork
                        ->where('progress_pekerjaan', 100));
            })
            ->whereHas('latestApprovedHpp')
            ->whereHas('garansi')
            ->whereDoesntHave('lhppBasts', fn (Builder $bast): Builder => $bast->where('termin_type', 'termin_1'));
    }

    /**
     * @param  Builder<Order>  $query
     * @return array<int, true>
     */
    private function idSet(Builder $query): array
    {
        /** @var Collection<int, int|string> $ids */
        $ids = $query->pluck('orders.id');

        return $ids->mapWithKeys(fn ($id): array => [(int) $id => true])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function relations(): array
    {
        return [
            'scopeOfWork:id,order_id',
            'latestHpp:hpps.id,hpps.order_id,hpps.status',
            'purchaseOrder:id,order_id,purchase_order_number,approve_manager,progress_pekerjaan',
            'initialWork:id,order_id,progress_pekerjaan',
            'lhppBasts:id,order_id,parent_lhpp_bast_id,termin_type',
        ];
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
