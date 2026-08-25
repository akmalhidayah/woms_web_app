<?php

declare(strict_types=1);

namespace App\Support;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\InitialWork;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\BengkelTasks\WorkshopHandoverQueue;
use App\Services\BengkelTasks\WorkshopQualityControlQueue;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class AdminActionCenter
{
    public const MODULE_ORDER_INCOMPLETE = 'order_jasa_incomplete';

    public const MODULE_CREATE_HPP = 'create_hpp';

    public const MODULE_BUDGET_VERIFICATION = 'verifikasi_anggaran';

    public const MODULE_PURCHASE_ORDER = 'purchase_order';

    public const MODULE_SET_GARANSI = 'set_garansi';

    public const MODULE_CHECK_BAST = 'cek_bast';

    public const MODULE_LPJ_PPL = 'lpj_ppl';

    /** @var array<string, array<string, int>> */
    private array $moduleCountsCache = [];

    /** @var array<string, Collection<int, array<string, mixed>>> */
    private array $actionsCache = [];

    public function __construct(
        private readonly BudgetVerificationIndexTabs $budgetVerificationTabs,
        private readonly PurchaseOrderIndexTabs $purchaseOrderTabs,
        private readonly WorkshopReadiness $workshopReadiness,
        private readonly WorkshopQualityControlQueue $workshopQualityControlQueue,
        private readonly WorkshopHandoverQueue $workshopHandoverQueue,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function actions(?User $user, int $limit = 10): Collection
    {
        if (! $user?->isAdmin() || $limit < 1) {
            return collect();
        }

        $cacheKey = $user->getKey().':'.$limit;

        return $this->actionsCache[$cacheKey] ??= collect($this->moduleDefinitions())
            ->filter(fn (array $definition): bool => AdminMenuRegistry::canAccess($user, $definition['menu_key']))
            ->flatMap(fn (array $definition, string $module): Collection => $this->actionsForModule($module, $limit))
            ->unique('key')
            ->sort(function (array $left, array $right): int {
                $levelOrder = ['danger' => 3, 'warning' => 2, 'normal' => 1];
                $levelComparison = ($levelOrder[$right['overdue_level']] ?? 0)
                    <=> ($levelOrder[$left['overdue_level']] ?? 0);

                if ($levelComparison !== 0) {
                    return $levelComparison;
                }

                return ($right['waiting_seconds'] ?? 0) <=> ($left['waiting_seconds'] ?? 0);
            })
            ->values()
            ->take($limit);
    }

    public function pendingActionCount(?User $user): int
    {
        return array_sum($this->moduleCounts($user));
    }

    /**
     * @return array<string, int>
     */
    public function moduleCounts(?User $user = null): array
    {
        if ($user !== null && ! $user->isAdmin()) {
            return array_fill_keys(array_keys($this->moduleDefinitions()), 0);
        }

        $cacheKey = $user ? 'user:'.$user->getKey() : 'all';

        if (isset($this->moduleCountsCache[$cacheKey])) {
            return $this->moduleCountsCache[$cacheKey];
        }

        $counts = [];

        foreach ($this->moduleDefinitions() as $module => $definition) {
            $canAccess = $user === null || AdminMenuRegistry::canAccess($user, $definition['menu_key']);
            $counts[$module] = $canAccess ? $this->countForModule($module) : 0;
        }

        return $this->moduleCountsCache[$cacheKey] = $counts;
    }

    /**
     * Badge values consumed by AdminMenuRegistry.
     *
     * @return array<string, int>
     */
    public function sidebarCounts(?User $user = null): array
    {
        $counts = $this->moduleCounts($user);

        return [
            'order_jasa_incomplete' => $counts[self::MODULE_ORDER_INCOMPLETE],
            'orders_total' => $counts[self::MODULE_ORDER_INCOMPLETE],
            'create_hpp' => $counts[self::MODULE_CREATE_HPP],
            'verifikasi_anggaran' => $counts[self::MODULE_BUDGET_VERIFICATION],
            'purchase_order' => $counts[self::MODULE_PURCHASE_ORDER],
            'set_garansi' => $counts[self::MODULE_SET_GARANSI],
            'cek_bast' => $counts[self::MODULE_CHECK_BAST],
            'bast_total' => $counts[self::MODULE_SET_GARANSI] + $counts[self::MODULE_CHECK_BAST],
            'lpj_ppl' => $counts[self::MODULE_LPJ_PPL],
            'order_bengkel_incomplete' => ($user === null || AdminMenuRegistry::canAccess($user, AdminMenuRegistry::MENU_ORDER_BENGKEL))
                ? $this->workshopReadiness->applyIncomplete($this->workshopOrderQuery())->count()
                : 0,
            'quality_control_bengkel' => ($user === null || AdminMenuRegistry::canAccess($user, AdminMenuRegistry::MENU_QUALITY_CONTROL_BENGKEL))
                ? $this->workshopQualityControlQueue->actionCount()
                : 0,
            'serah_terima_bengkel' => ($user === null || AdminMenuRegistry::canAccess($user, AdminMenuRegistry::MENU_SERAH_TERIMA_BENGKEL))
                ? $this->workshopHandoverQueue->count()
                : 0,
        ];
    }

    private function workshopOrderQuery(): Builder
    {
        return Order::query()->whereIn('catatan_status', [
            OrderUserNoteStatus::ApprovedWorkshop->value,
            OrderUserNoteStatus::ApprovedWorkshopJasa->value,
        ]);
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    public function summary(?User $user, int $limit = 4): array
    {
        $counts = $this->moduleCounts($user);

        return collect($this->moduleDefinitions())
            ->map(fn (array $definition, string $module): array => [
                'label' => $definition['summary_label'],
                'count' => $counts[$module] ?? 0,
            ])
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->sortByDesc('count')
            ->take(max(1, $limit))
            ->values()
            ->all();
    }

    /**
     * @return array{waiting_text: string, overdue_level: string, is_overdue: bool, waiting_seconds: int}
     */
    public function waitingState(?CarbonInterface $startedAt): array
    {
        if ($startedAt === null) {
            return [
                'waiting_text' => 'Menunggu tindak lanjut',
                'overdue_level' => 'normal',
                'is_overdue' => false,
                'waiting_seconds' => 0,
            ];
        }

        $seconds = max(0, now()->timestamp - $startedAt->timestamp);
        $hours = intdiv($seconds, 3600);
        $days = intdiv($seconds, 86400);

        if ($hours >= 48) {
            return [
                'waiting_text' => 'Terlambat '.max(2, $days).' hari',
                'overdue_level' => 'danger',
                'is_overdue' => true,
                'waiting_seconds' => $seconds,
            ];
        }

        if ($hours >= 24) {
            return [
                'waiting_text' => 'Menunggu 1 hari',
                'overdue_level' => 'warning',
                'is_overdue' => true,
                'waiting_seconds' => $seconds,
            ];
        }

        return [
            'waiting_text' => 'Menunggu '.max(1, $hours).' jam',
            'overdue_level' => 'normal',
            'is_overdue' => false,
            'waiting_seconds' => $seconds,
        ];
    }

    /**
     * @return array<string, array{menu_key: string, summary_label: string}>
     */
    private function moduleDefinitions(): array
    {
        return [
            self::MODULE_ORDER_INCOMPLETE => [
                'menu_key' => AdminMenuRegistry::MENU_ORDER_JASA,
                'summary_label' => 'Order perlu dilengkapi',
            ],
            self::MODULE_CREATE_HPP => [
                'menu_key' => AdminMenuRegistry::MENU_CREATE_HPP,
                'summary_label' => 'HPP perlu dibuat',
            ],
            self::MODULE_BUDGET_VERIFICATION => [
                'menu_key' => AdminMenuRegistry::MENU_VERIFIKASI_ANGGARAN,
                'summary_label' => 'Verifikasi Anggaran',
            ],
            self::MODULE_PURCHASE_ORDER => [
                'menu_key' => AdminMenuRegistry::MENU_PURCHASE_ORDER,
                'summary_label' => 'Purchase Order',
            ],
            self::MODULE_SET_GARANSI => [
                'menu_key' => AdminMenuRegistry::MENU_GARANSI,
                'summary_label' => 'Set Garansi',
            ],
            self::MODULE_CHECK_BAST => [
                'menu_key' => AdminMenuRegistry::MENU_LHPP_BAST,
                'summary_label' => 'BAST perlu dicek',
            ],
            self::MODULE_LPJ_PPL => [
                'menu_key' => AdminMenuRegistry::MENU_LPJ_PPL,
                'summary_label' => 'LPJ/PPL perlu dilengkapi',
            ],
        ];
    }

    private function countForModule(string $module): int
    {
        return match ($module) {
            self::MODULE_ORDER_INCOMPLETE => $this->orderIncompleteQuery()->count(),
            self::MODULE_CREATE_HPP => $this->createHppQuery()->count(),
            self::MODULE_BUDGET_VERIFICATION => $this->budgetVerificationTabs
                ->countFor(BudgetVerificationIndexTabs::TAB_ACTION),
            self::MODULE_PURCHASE_ORDER => $this->purchaseOrderTabs
                ->countFor(PurchaseOrderIndexTabs::TAB_ACTION),
            self::MODULE_SET_GARANSI => $this->setGaransiQuery()->count(),
            self::MODULE_CHECK_BAST => $this->checkBastQuery()->count(),
            self::MODULE_LPJ_PPL => $this->lpjPplQuery()->count(),
            default => 0,
        };
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function actionsForModule(string $module, int $limit): Collection
    {
        return match ($module) {
            self::MODULE_ORDER_INCOMPLETE => $this->orderIncompleteActions($limit),
            self::MODULE_CREATE_HPP => $this->createHppActions($limit),
            self::MODULE_BUDGET_VERIFICATION => $this->budgetVerificationActions($limit),
            self::MODULE_PURCHASE_ORDER => $this->purchaseOrderActions($limit),
            self::MODULE_SET_GARANSI => $this->setGaransiActions($limit),
            self::MODULE_CHECK_BAST => $this->checkBastActions($limit),
            self::MODULE_LPJ_PPL => $this->lpjPplActions($limit),
            default => collect(),
        };
    }

    private function orderIncompleteQuery(): Builder
    {
        return Order::query()
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->whereDoesntHave('scopeOfWork');
    }

    private function createHppQuery(): Builder
    {
        return Order::query()
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->whereHas('scopeOfWork')
            ->doesntHave('hpps');
    }

    private function budgetVerificationQuery(): Builder
    {
        return $this->budgetVerificationTabs->apply(
            $this->budgetVerificationTabs->baseQuery(),
            BudgetVerificationIndexTabs::TAB_ACTION,
        );
    }

    private function purchaseOrderQuery(): Builder
    {
        return $this->purchaseOrderTabs->apply(
            $this->purchaseOrderTabs->baseQuery(),
            PurchaseOrderIndexTabs::TAB_ACTION,
        );
    }

    private function setGaransiQuery(): Builder
    {
        return Order::query()
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('purchaseOrder', function (Builder $purchaseOrder): void {
                        $purchaseOrder
                            ->whereNotNull('purchase_order_number')
                            ->whereRaw("TRIM(purchase_order_number) <> ''")
                            ->where('progress_pekerjaan', 100);
                    })
                    ->orWhereHas('initialWork', fn (Builder $initialWork): Builder => $initialWork
                        ->where('progress_pekerjaan', 100));
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereDoesntHave('garansi')
                    ->orWhereHas('garansi', fn (Builder $garansi): Builder => $garansi->whereNull('garansi_months'));
            });
    }

    private function checkBastQuery(): Builder
    {
        return LhppBast::query()
            ->where('termin_type', 'termin_1')
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('quality_control_status')
                    ->orWhereRaw("TRIM(quality_control_status) = ''")
                    ->orWhere('quality_control_status', 'pending');
            });
    }

    private function lpjPplQuery(): Builder
    {
        return LhppBast::query()
            ->where('termin_type', 'termin_1')
            ->where('approval_status', LhppBast::APPROVAL_APPROVED)
            ->where(function (Builder $query): void {
                $query
                    ->where(function (Builder $terminOne): void {
                        $terminOne
                            ->whereDoesntHave('lpjPpl')
                            ->orWhereHas('lpjPpl', fn (Builder $lpjPpl): Builder => $this->whereAnyBlank($lpjPpl, [
                                'lpj_number_termin1',
                                'ppl_number_termin1',
                                'lpj_document_path_termin1',
                                'ppl_document_path_termin1',
                            ]));
                    })
                    ->orWhere(function (Builder $terminTwo): void {
                        $terminTwo
                            ->where('termin1_status', 'sudah')
                            ->whereHas('garansi', fn (Builder $garansi): Builder => $garansi
                                ->where('garansi_months', '>', 0))
                            ->whereHas('terminTwo', fn (Builder $child): Builder => $child
                                ->where('approval_status', LhppBast::APPROVAL_APPROVED))
                            ->where(function (Builder $missingTerminTwo): void {
                                $missingTerminTwo
                                    ->whereDoesntHave('lpjPpl')
                                    ->orWhereHas('lpjPpl', fn (Builder $lpjPpl): Builder => $this->whereAnyBlank($lpjPpl, [
                                        'lpj_number_termin2',
                                        'ppl_number_termin2',
                                        'lpj_document_path_termin2',
                                        'ppl_document_path_termin2',
                                    ]));
                            });
                    });
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function orderIncompleteActions(int $limit): Collection
    {
        return $this->orderIncompleteQuery()
            ->oldest('updated_at')
            ->limit($limit)
            ->get(['id', 'nomor_order', 'nama_pekerjaan', 'updated_at'])
            ->map(fn (Order $order): array => $this->action(
                key: 'order-sow:'.$order->id,
                menuKey: AdminMenuRegistry::MENU_ORDER_JASA,
                type: 'Order',
                title: 'Lengkapi Order',
                message: 'Order '.$this->orderNumber($order).' belum memiliki Scope of Work.',
                meta: $order->nama_pekerjaan,
                icon: 'clipboard-list',
                tone: 'amber',
                url: route('admin.orders.index', ['search' => $order->nomor_order]),
                actionLabel: 'Lengkapi Order',
                startedAt: $order->updated_at,
            ));
    }

    /** @return Collection<int, array<string, mixed>> */
    private function createHppActions(int $limit): Collection
    {
        return $this->createHppQuery()
            ->with('scopeOfWork:id,order_id,created_at')
            ->oldest('updated_at')
            ->limit($limit)
            ->get(['id', 'nomor_order', 'nama_pekerjaan', 'updated_at'])
            ->map(fn (Order $order): array => $this->action(
                key: 'create-hpp:'.$order->id,
                menuKey: AdminMenuRegistry::MENU_CREATE_HPP,
                type: 'HPP',
                title: 'Buat HPP',
                message: 'Order '.$this->orderNumber($order).' siap dibuatkan HPP.',
                meta: $order->nama_pekerjaan,
                icon: 'file-plus-2',
                tone: 'blue',
                url: route('admin.hpp.index', ['search' => $order->nomor_order]),
                actionLabel: 'Buat HPP',
                startedAt: $order->scopeOfWork?->created_at ?? $order->updated_at,
            ));
    }

    /** @return Collection<int, array<string, mixed>> */
    private function budgetVerificationActions(int $limit): Collection
    {
        return $this->budgetVerificationQuery()
            ->with('order:id,nomor_order,nama_pekerjaan')
            ->withMax([
                'signatures as latest_signed_at' => fn (Builder $query): Builder => $query
                    ->where('status', HppSignature::STATUS_SIGNED),
            ], 'signed_at')
            ->oldest('hpps.updated_at')
            ->limit($limit)
            ->get(['hpps.id', 'hpps.order_id', 'hpps.nomor_order', 'hpps.nama_pekerjaan', 'hpps.updated_at'])
            ->map(fn (Hpp $hpp): array => $this->action(
                key: 'budget-verification:'.$hpp->id,
                menuKey: AdminMenuRegistry::MENU_VERIFIKASI_ANGGARAN,
                type: 'Verifikasi Anggaran',
                title: 'Verifikasi Anggaran',
                message: 'HPP order '.$this->hppOrderNumber($hpp).' menunggu verifikasi anggaran.',
                meta: $hpp->nama_pekerjaan ?: $hpp->order?->nama_pekerjaan,
                icon: 'wallet-cards',
                tone: 'blue',
                url: route('admin.budget-verification.index', ['search' => $this->hppOrderNumber($hpp)]),
                actionLabel: 'Verifikasi Anggaran',
                startedAt: $this->toCarbon($hpp->getAttribute('latest_signed_at')) ?? $hpp->updated_at,
            ));
    }

    /** @return Collection<int, array<string, mixed>> */
    private function purchaseOrderActions(int $limit): Collection
    {
        return $this->purchaseOrderQuery()
            ->with([
                'order:id,nomor_order,nama_pekerjaan',
                'budgetVerification:id,hpp_id,created_at,updated_at',
            ])
            ->oldest('hpps.updated_at')
            ->limit($limit)
            ->get(['hpps.id', 'hpps.order_id', 'hpps.nomor_order', 'hpps.nama_pekerjaan', 'hpps.updated_at'])
            ->map(fn (Hpp $hpp): array => $this->action(
                key: 'purchase-order:'.$hpp->id,
                menuKey: AdminMenuRegistry::MENU_PURCHASE_ORDER,
                type: 'Purchase Order',
                title: 'Lengkapi PO',
                message: 'Order '.$this->hppOrderNumber($hpp).' siap dilengkapi Purchase Order.',
                meta: $hpp->nama_pekerjaan ?: $hpp->order?->nama_pekerjaan,
                icon: 'list-checks',
                tone: 'blue',
                url: route('admin.purchase-order.index', ['search' => $this->hppOrderNumber($hpp)]),
                actionLabel: 'Lengkapi PO',
                startedAt: $hpp->budgetVerification?->updated_at
                    ?? $hpp->budgetVerification?->created_at
                    ?? $hpp->updated_at,
            ));
    }

    /** @return Collection<int, array<string, mixed>> */
    private function setGaransiActions(int $limit): Collection
    {
        return $this->setGaransiQuery()
            ->with([
                'purchaseOrder:id,order_id,progress_pekerjaan,tanggal_selesai_pekerjaan,updated_at',
                'initialWork:id,order_id,progress_pekerjaan,tanggal_selesai_pekerjaan,updated_at',
            ])
            ->oldest('updated_at')
            ->limit($limit)
            ->get(['id', 'nomor_order', 'nama_pekerjaan', 'updated_at'])
            ->map(fn (Order $order): array => $this->action(
                key: 'set-garansi:'.$order->id,
                menuKey: AdminMenuRegistry::MENU_GARANSI,
                type: 'Garansi',
                title: 'Set Garansi',
                message: 'Pekerjaan order '.$this->orderNumber($order).' telah selesai 100% dan menunggu set garansi.',
                meta: $order->nama_pekerjaan,
                icon: 'shield-check',
                tone: 'amber',
                url: route('admin.garansi.index', ['search' => $order->nomor_order]),
                actionLabel: 'Set Garansi',
                startedAt: $this->garansiStartedAt($order),
            ));
    }

    /** @return Collection<int, array<string, mixed>> */
    private function checkBastActions(int $limit): Collection
    {
        return $this->checkBastQuery()
            ->oldest('created_at')
            ->limit($limit)
            ->get(['id', 'nomor_order', 'deskripsi_pekerjaan', 'created_at'])
            ->map(fn (LhppBast $lhpp): array => $this->action(
                key: 'check-bast:'.$lhpp->id,
                menuKey: AdminMenuRegistry::MENU_LHPP_BAST,
                type: 'BAST',
                title: 'Cek BAST',
                message: 'BAST order '.($lhpp->nomor_order ?: '-').' menunggu pemeriksaan Quality Control.',
                meta: $lhpp->deskripsi_pekerjaan,
                icon: 'clipboard-check',
                tone: 'amber',
                url: route('admin.lhpp.index', ['search' => $lhpp->nomor_order]),
                actionLabel: 'Cek BAST',
                startedAt: $lhpp->created_at,
            ));
    }

    /** @return Collection<int, array<string, mixed>> */
    private function lpjPplActions(int $limit): Collection
    {
        return $this->lpjPplQuery()
            ->withMax([
                'signatures as latest_signed_at' => fn (Builder $query): Builder => $query
                    ->where('status', LhppBastSignature::STATUS_SIGNED),
            ], 'signed_at')
            ->oldest('updated_at')
            ->limit($limit)
            ->get(['id', 'nomor_order', 'deskripsi_pekerjaan', 'updated_at'])
            ->map(fn (LhppBast $lhpp): array => $this->action(
                key: 'lpj-ppl:'.$lhpp->id,
                menuKey: AdminMenuRegistry::MENU_LPJ_PPL,
                type: 'LPJ / PPL',
                title: 'Lengkapi LPJ/PPL',
                message: 'Dokumen LPJ/PPL order '.($lhpp->nomor_order ?: '-').' belum lengkap.',
                meta: $lhpp->deskripsi_pekerjaan,
                icon: 'folder-open',
                tone: 'amber',
                url: route('admin.lpj.index', ['search' => $lhpp->nomor_order]),
                actionLabel: 'Lengkapi LPJ/PPL',
                startedAt: $this->toCarbon($lhpp->getAttribute('latest_signed_at')) ?? $lhpp->updated_at,
            ));
    }

    /**
     * @param  list<string>  $columns
     */
    private function whereAnyBlank(Builder $query, array $columns): Builder
    {
        return $query->where(function (Builder $blank) use ($columns): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';

                $blank->{$method}(function (Builder $value) use ($column): void {
                    $value
                        ->whereNull($column)
                        ->orWhereRaw("TRIM({$column}) = ''");
                });
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function action(
        string $key,
        string $menuKey,
        string $type,
        string $title,
        string $message,
        ?string $meta,
        string $icon,
        string $tone,
        string $url,
        string $actionLabel,
        ?CarbonInterface $startedAt,
    ): array {
        return [
            'key' => $key,
            'menu_key' => $menuKey,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'meta' => filled($meta) ? trim((string) $meta) : null,
            'icon' => $icon,
            'tone' => $tone,
            'url' => $url,
            'action_label' => $actionLabel,
            'started_at' => $startedAt,
            ...$this->waitingState($startedAt),
        ];
    }

    private function garansiStartedAt(Order $order): ?CarbonInterface
    {
        $sources = collect([$order->purchaseOrder, $order->initialWork])
            ->filter(fn (PurchaseOrder|InitialWork|null $source): bool => $source !== null
                && (int) $source->progress_pekerjaan >= 100)
            ->map(fn (PurchaseOrder|InitialWork $source): ?CarbonInterface => $source->tanggal_selesai_pekerjaan
                ?? $source->updated_at)
            ->filter();

        return $sources->sortBy(fn (CarbonInterface $date): int => $date->timestamp)->first()
            ?? $order->updated_at;
    }

    private function orderNumber(Order $order): string
    {
        return $order->nomor_order ?: '-';
    }

    private function hppOrderNumber(Hpp $hpp): string
    {
        return $hpp->nomor_order ?: $hpp->order?->nomor_order ?: '-';
    }

    private function toCarbon(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }
}
