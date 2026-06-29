<?php

namespace App\Support;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\InitialWork;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\PkmNotificationRead;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PkmNotificationCenter
{
    private const RECENT_DAYS = 14;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function notifications(int $limit = 5, ?User $user = null): Collection
    {
        $sourceLimit = $user ? max($limit * 4, 20) : $limit;

        return self::allNotifications($sourceLimit)
            ->reject(fn (array $notification): bool => self::isRead($notification, $user))
            ->sortByDesc('occurred_at')
            ->values()
            ->take($limit);
    }

    public static function notificationCount(?User $user = null): int
    {
        return self::allNotifications()
            ->reject(fn (array $notification): bool => self::isRead($notification, $user))
            ->count();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function allNotifications(?int $limit = null): Collection
    {
        return collect()
            ->merge(self::purchaseOrderJobWaitingNotifications($limit))
            ->merge(self::emergencyJobWaitingNotifications($limit))
            ->merge(self::bastSignedNotifications($limit))
            ->merge(self::targetApprovedNotifications($limit));
    }

    private static function isRead(array $notification, ?User $user): bool
    {
        if (! $user || ! isset($notification['key'])) {
            return false;
        }

        return self::readKeysForUser($user)->contains($notification['key']);
    }

    /**
     * @return Collection<int, string>
     */
    private static function readKeysForUser(User $user): Collection
    {
        static $cache = [];

        if (! Schema::hasTable('pkm_notification_reads')) {
            return collect();
        }

        return $cache[$user->id] ??= PkmNotificationRead::query()
            ->where('user_id', $user->id)
            ->pluck('notification_key');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function purchaseOrderJobWaitingNotifications(?int $limit): Collection
    {
        return self::limitQuery(PurchaseOrder::query()
            ->with('order:id,nomor_order,notifikasi,nama_pekerjaan,catatan_status')
            ->where('approve_manager', true)
            ->whereNotNull('purchase_order_number')
            ->whereRaw("TRIM(purchase_order_number) <> ''")
            ->whereHas('order', fn ($query) => $query->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])->where(self::activeJobWaitingScope()))
            ->where('updated_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->latest('updated_at'), $limit)
            ->get()
            ->map(fn (PurchaseOrder $purchaseOrder): array => [
                'key' => 'pkm-job-po:'.$purchaseOrder->id,
                'type' => 'Pekerjaan',
                'icon' => 'briefcase-business',
                'tone' => 'blue',
                'message' => sprintf(
                    'Order %s sudah masuk job waiting melalui PO %s.',
                    $purchaseOrder->order?->nomor_order ?: '-',
                    $purchaseOrder->purchase_order_number ?: '-',
                ),
                'meta' => $purchaseOrder->order?->nama_pekerjaan ?: 'Pekerjaan PO',
                'occurred_at' => $purchaseOrder->updated_at ?: $purchaseOrder->created_at,
                'url' => route('pkm.jobwaiting', ['search' => $purchaseOrder->order?->nomor_order]),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function emergencyJobWaitingNotifications(?int $limit): Collection
    {
        return self::limitQuery(InitialWork::query()
            ->with('order:id,nomor_order,notifikasi,nama_pekerjaan,prioritas,catatan_status')
            ->whereHas('order', function ($query): void {
                $query
                    ->whereIn('catatan_status', [
                        OrderUserNoteStatus::ApprovedJasa->value,
                        OrderUserNoteStatus::ApprovedWorkshopJasa->value,
                    ])
                    ->whereIn('prioritas', [
                        Order::PRIORITY_URGENT,
                        Order::PRIORITY_HIGH,
                    ])
                    ->where(self::activeJobWaitingScope());
            })
            ->where('created_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->latest('created_at'), $limit)
            ->get()
            ->map(fn (InitialWork $initialWork): array => [
                'key' => 'pkm-job-emergency:'.$initialWork->id,
                'type' => 'Emergency',
                'icon' => 'siren',
                'tone' => 'rose',
                'message' => sprintf(
                    'Order emergency %s sudah masuk job waiting melalui Initial Work.',
                    $initialWork->order?->nomor_order ?: $initialWork->nomor_order ?: '-',
                ),
                'meta' => $initialWork->order?->nama_pekerjaan ?: $initialWork->nama_pekerjaan ?: 'Initial Work',
                'occurred_at' => $initialWork->created_at ?: $initialWork->tanggal_initial_work,
                'url' => route('pkm.jobwaiting', ['search' => $initialWork->order?->nomor_order ?: $initialWork->nomor_order]),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function bastSignedNotifications(?int $limit): Collection
    {
        return self::limitQuery(LhppBastSignature::query()
            ->with('lhppBast:id,nomor_order,termin_type,deskripsi_pekerjaan')
            ->where('status', LhppBastSignature::STATUS_SIGNED)
            ->whereNotNull('signed_at')
            ->where('signed_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->latest('signed_at'), $limit)
            ->get()
            ->map(function (LhppBastSignature $signature): array {
                $termin = $signature->lhppBast?->termin_type === 'termin_2' ? 'Termin 2' : 'Termin 1';

                return [
                    'key' => 'pkm-bast-signature:'.$signature->id,
                    'type' => 'BAST',
                    'icon' => 'file-signature',
                    'tone' => 'emerald',
                    'message' => sprintf(
                        'BAST %s order %s sudah ditandatangani oleh %s.',
                        $termin,
                        $signature->lhppBast?->nomor_order ?: '-',
                        $signature->signer_name_snapshot ?: 'Approver',
                    ),
                    'meta' => $signature->displayRoleLabel(),
                    'occurred_at' => $signature->signed_at,
                    'url' => route('pkm.lhpp.index', ['search' => $signature->lhppBast?->nomor_order]),
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function targetApprovedNotifications(?int $limit): Collection
    {
        return self::limitQuery(PurchaseOrder::query()
            ->with('order:id,nomor_order,nama_pekerjaan')
            ->where('approval_target', 'setuju')
            ->whereNotNull('target_penyelesaian')
            ->where(function ($query): void {
                $query
                    ->whereNull('progress_pekerjaan')
                    ->orWhere('progress_pekerjaan', '<', 11);
            })
            ->where('updated_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->latest('updated_at'), $limit)
            ->get()
            ->map(fn (PurchaseOrder $purchaseOrder): array => [
                'key' => 'pkm-target-approved:'.$purchaseOrder->id.':'.$purchaseOrder->target_penyelesaian?->toDateString(),
                'type' => 'Target',
                'icon' => 'calendar-check',
                'tone' => 'amber',
                'message' => sprintf(
                    'Target penyelesaian order %s disetujui. Pekerjaan sudah bisa dimulai.',
                    $purchaseOrder->order?->nomor_order ?: '-',
                ),
                'meta' => 'Target '.$purchaseOrder->target_penyelesaian?->format('d/m/Y'),
                'occurred_at' => $purchaseOrder->updated_at,
                'url' => route('pkm.jobwaiting', ['search' => $purchaseOrder->order?->nomor_order]),
            ]);
    }

    private static function limitQuery($query, ?int $limit)
    {
        return $limit === null ? $query : $query->limit($limit);
    }

    private static function activeJobWaitingScope(): \Closure
    {
        return function ($query): void {
            $query
                ->doesntHave('latestHpp')
                ->orWhereDoesntHave('lhppBasts', function ($bastQuery): void {
                    $bastQuery
                        ->where('termin_type', 'termin_1')
                        ->whereHas('garansi')
                        ->whereHas('lpjPpl', function ($lpjPplQuery): void {
                            $lpjPplQuery
                                ->whereNotNull('lpj_document_path_termin1')
                                ->whereNotNull('ppl_document_path_termin1');
                        });
                });
        };
    }
}
