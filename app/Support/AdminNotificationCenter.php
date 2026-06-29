<?php

namespace App\Support;

use App\Models\AdminNotificationRead;
use App\Models\HppSignature;
use App\Models\InitialWork;
use App\Models\InitialWorkSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AdminNotificationCenter
{
    private const RECENT_DAYS = 7;

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public static function signatureNotifications(int $limit = 5, ?User $user = null): Collection
    {
        $sourceLimit = $user ? max($limit * 4, 20) : $limit;

        return self::allNotifications($sourceLimit)
            ->reject(fn (array $notification): bool => self::isRead($notification, $user))
            ->sortByDesc('signed_at')
            ->values()
            ->take($limit);
    }

    public static function signatureNotificationCount(?User $user = null): int
    {
        return self::allNotifications()
            ->reject(fn (array $notification): bool => self::isRead($notification, $user))
            ->count();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private static function allNotifications(?int $limit = null): Collection
    {
        return collect()
            ->merge(self::hppSignedNotifications($limit))
            ->merge(self::initialWorkSignedNotifications($limit))
            ->merge(self::bastSignedNotifications($limit))
            ->merge(self::bastQualityControlNotifications($limit))
            ->merge(self::pkmPurchaseOrderProgressNotifications($limit))
            ->merge(self::pkmInitialWorkProgressNotifications($limit));
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

        if (! Schema::hasTable('admin_notification_reads')) {
            return collect();
        }

        return $cache[$user->id] ??= AdminNotificationRead::query()
            ->where('user_id', $user->id)
            ->pluck('notification_key');
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private static function hppSignedNotifications(?int $limit): Collection
    {
        return self::limitQuery(HppSignature::query()
            ->with('hpp:id,nomor_order,nama_pekerjaan')
            ->where('status', HppSignature::STATUS_SIGNED)
            ->whereNotNull('signed_at')
            ->where('signed_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->latest('signed_at'), $limit)
            ->get()
            ->map(fn (HppSignature $signature): array => [
                'key' => 'hpp-signature:'.$signature->id,
                'type' => 'HPP',
                'icon' => 'file-signature',
                'tone' => 'blue',
                'title' => 'HPP ditandatangani',
                'message' => sprintf(
                    'Order %s HPP telah ditandatangani oleh %s.',
                    $signature->hpp?->nomor_order ?: '-',
                    $signature->signer_name_snapshot ?: 'Approver',
                ),
                'meta' => $signature->role_label,
                'signed_at' => $signature->signed_at,
                'url' => route('admin.hpp.index', ['search' => $signature->hpp?->nomor_order]),
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private static function initialWorkSignedNotifications(?int $limit): Collection
    {
        return self::limitQuery(InitialWorkSignature::query()
            ->with('initialWork:id,order_id,nomor_order,nomor_initial_work,nama_pekerjaan')
            ->where('status', InitialWorkSignature::STATUS_SIGNED)
            ->whereNotNull('signed_at')
            ->where('signed_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->latest('signed_at'), $limit)
            ->get()
            ->map(fn (InitialWorkSignature $signature): array => [
                'key' => 'initial-work-signature:'.$signature->id,
                'type' => 'Initial Work',
                'icon' => 'clipboard-pen-line',
                'tone' => 'amber',
                'title' => 'Initial Work ditandatangani',
                'message' => sprintf(
                    'Order %s Initial Work telah ditandatangani oleh %s.',
                    $signature->initialWork?->nomor_order ?: '-',
                    $signature->signer_name ?: 'Approver',
                ),
                'meta' => $signature->role_label,
                'signed_at' => $signature->signed_at,
                'url' => route('admin.orders.index', ['search' => $signature->initialWork?->nomor_order]),
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
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
            ->map(fn (LhppBastSignature $signature): array => [
                'key' => 'bast-signature:'.$signature->id,
                'type' => 'BAST',
                'icon' => 'file-badge',
                'tone' => 'emerald',
                'title' => 'BAST ditandatangani',
                'message' => sprintf(
                    'Order %s BAST %s telah ditandatangani oleh %s.',
                    $signature->lhppBast?->nomor_order ?: '-',
                    $signature->lhppBast?->termin_type === 'termin_2' ? 'Termin 2' : 'Termin 1',
                    $signature->signer_name_snapshot ?: 'Approver',
                ),
                'meta' => $signature->role_label,
                'signed_at' => $signature->signed_at,
                'url' => route('admin.lhpp.index', ['search' => $signature->lhppBast?->nomor_order]),
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private static function bastQualityControlNotifications(?int $limit): Collection
    {
        return self::limitQuery(LhppBast::query()
            ->where('quality_control_status', 'pending')
            ->where('termin_type', 'termin_1')
            ->where('created_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->latest('created_at'), $limit)
            ->get()
            ->map(fn (LhppBast $lhpp): array => [
                'key' => 'bast-quality-control:'.$lhpp->id,
                'type' => 'BAST',
                'icon' => 'clipboard-check',
                'tone' => 'amber',
                'title' => 'Cek quality control BAST',
                'message' => sprintf(
                    'PKM membuat BAST %s untuk order %s. Cek quality control untuk mulai token TTD.',
                    $lhpp->termin_type === 'termin_2' ? 'Termin 2' : 'Termin 1',
                    $lhpp->nomor_order ?: '-',
                ),
                'meta' => 'Menunggu QC Admin',
                'signed_at' => $lhpp->created_at,
                'url' => route('admin.lhpp.index', ['search' => $lhpp->nomor_order]),
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private static function pkmPurchaseOrderProgressNotifications(?int $limit): Collection
    {
        return self::limitQuery(self::pkmPurchaseOrderProgressQuery()
            ->with('order:id,nomor_order,nama_pekerjaan')
            ->where('updated_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->latest('updated_at'), $limit)
            ->get()
            ->map(fn (PurchaseOrder $purchaseOrder): array => self::mapPkmProgressNotification(
                key: 'pkm-po-progress:'.$purchaseOrder->id.':'.($purchaseOrder->updated_at?->timestamp ?? 0),
                nomorOrder: $purchaseOrder->order?->nomor_order ?: '-',
                sourceLabel: 'PO',
                progress: (int) ($purchaseOrder->progress_pekerjaan ?? 0),
                targetDate: $purchaseOrder->target_penyelesaian,
                updatedAt: $purchaseOrder->updated_at,
                url: route('admin.orders.index', ['search' => $purchaseOrder->order?->nomor_order]),
            ));
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private static function pkmInitialWorkProgressNotifications(?int $limit): Collection
    {
        return self::limitQuery(self::pkmInitialWorkProgressQuery()
            ->where('updated_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->latest('updated_at'), $limit)
            ->get()
            ->map(fn (InitialWork $initialWork): array => self::mapPkmProgressNotification(
                key: 'pkm-initial-work-progress:'.$initialWork->id.':'.($initialWork->updated_at?->timestamp ?? 0),
                nomorOrder: $initialWork->nomor_order ?: '-',
                sourceLabel: 'Initial Work',
                progress: (int) ($initialWork->progress_pekerjaan ?? 0),
                targetDate: $initialWork->target_penyelesaian,
                updatedAt: $initialWork->updated_at,
                url: route('admin.orders.index', ['search' => $initialWork->nomor_order]),
            ));
    }

    private static function pkmPurchaseOrderProgressQuery()
    {
        return PurchaseOrder::query()
            ->where(function ($query): void {
                $query
                    ->where('progress_pekerjaan', '>=', 11)
                    ->orWhereNotNull('tanggal_selesai_pekerjaan')
                    ->orWhere(function ($targetQuery): void {
                        $targetQuery
                            ->whereNotNull('target_penyelesaian')
                            ->whereHas('updater', fn ($userQuery) => $userQuery->where('role', User::ROLE_PKM));
                    });
            });
    }

    private static function pkmInitialWorkProgressQuery()
    {
        return InitialWork::query()
            ->where(function ($query): void {
                $query
                    ->where('progress_pekerjaan', '>=', 11)
                    ->orWhereNotNull('target_penyelesaian')
                    ->orWhereNotNull('tanggal_selesai_pekerjaan');
            });
    }

    private static function mapPkmProgressNotification(
        string $key,
        string $nomorOrder,
        string $sourceLabel,
        int $progress,
        mixed $targetDate,
        mixed $updatedAt,
        string $url,
    ): array {
        $targetLabel = $targetDate ? $targetDate->format('d/m/Y') : null;

        $message = match (true) {
            $progress >= 100 => sprintf(
                'PKM menyelesaikan pekerjaan order %s pada progress 100%%. Order sudah bisa diset garansi.',
                $nomorOrder,
            ),
            $progress >= 11 => sprintf(
                'PKM sudah start pekerjaan order %s dengan progress %s%%.',
                $nomorOrder,
                $progress,
            ),
            $targetLabel !== null => sprintf(
                'PKM set tanggal penyelesaian order %s ke %s.',
                $nomorOrder,
                $targetLabel,
            ),
            default => sprintf('PKM memperbarui pekerjaan order %s.', $nomorOrder),
        };

        if ($targetLabel && $progress >= 11 && $progress < 100) {
            $message .= ' Target selesai '.$targetLabel.'.';
        }

        return [
            'key' => $key,
            'type' => 'PKM',
            'icon' => $progress >= 100 ? 'shield-check' : 'activity',
            'tone' => $progress >= 100 ? 'emerald' : 'amber',
            'title' => $progress >= 100 ? 'Garansi siap diatur' : 'Progress PKM diperbarui',
            'message' => $message,
            'meta' => $progress >= 100 ? $sourceLabel.' / Set Garansi' : $sourceLabel,
            'signed_at' => $updatedAt,
            'url' => $progress >= 100 ? route('admin.garansi.index', ['search' => $nomorOrder]) : $url,
        ];
    }

    private static function limitQuery($query, ?int $limit)
    {
        return $limit === null ? $query : $query->limit($limit);
    }
}
