<?php

namespace App\Support;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\UserNotificationRead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class UserNotificationCenter
{
    private const RECENT_DAYS = 14;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function notifications(int $limit = 5, ?User $user = null): Collection
    {
        $sourceLimit = $user ? max($limit * 4, 20) : $limit;

        return self::allNotifications($sourceLimit, $user)
            ->reject(fn (array $notification): bool => self::isRead($notification, $user))
            ->sortByDesc('occurred_at')
            ->values()
            ->take($limit);
    }

    public static function notificationCount(?User $user = null): int
    {
        return self::allNotifications(null, $user)
            ->reject(fn (array $notification): bool => self::isRead($notification, $user))
            ->count();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function allNotifications(?int $limit, ?User $user): Collection
    {
        return self::approvedOrderNotifications($limit, $user);
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

        if (! Schema::hasTable('user_notification_reads')) {
            return collect();
        }

        return $cache[$user->id] ??= UserNotificationRead::query()
            ->where('user_id', $user->id)
            ->pluck('notification_key');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function approvedOrderNotifications(?int $limit, ?User $user): Collection
    {
        return self::limitQuery(Order::query()
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshop->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->where('updated_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->latest('updated_at'), $limit)
            ->get()
            ->map(fn (Order $order): array => [
                'key' => 'user-order-approved:'.$order->id.':'.($order->catatan_status?->value ?? 'approved'),
                'type' => 'Approved',
                'icon' => 'badge-check',
                'tone' => match ($order->catatan_status) {
                    OrderUserNoteStatus::ApprovedWorkshop => 'emerald',
                    OrderUserNoteStatus::ApprovedWorkshopJasa => 'blue',
                    default => 'amber',
                },
                'message' => sprintf(
                    'Order %s sudah %s.',
                    $order->nomor_order ?: '-',
                    $order->catatan_status?->label() ?? 'Approved',
                ),
                'meta' => $order->nama_pekerjaan ?: 'Nama pekerjaan belum diisi',
                'occurred_at' => $order->updated_at ?: $order->created_at,
                'url' => route('user.orders.show', $order),
            ]);
    }

    private static function limitQuery($query, ?int $limit)
    {
        return $limit === null ? $query : $query->limit($limit);
    }
}
