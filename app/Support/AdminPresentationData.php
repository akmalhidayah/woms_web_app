<?php

namespace App\Support;

use App\Models\User;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Short-lived display data only; never used to authorize or process documents. */
class AdminPresentationData
{
    public const TTL_SECONDS = 45;

    private array $memo = [];

    public function __construct(
        private readonly AdminActionCenter $actions,
        private readonly AdminNotificationCenter $notifications,
    ) {}

    public function summary(User $user): array
    {
        return $this->remember($user, 'summary', fn (): array => [
            'badges' => $this->actions->sidebarCounts($user),
            'pending_count' => $this->actions->pendingActionCount($user),
            'action_summary' => $this->actions->summary($user),
            'unread_count' => $this->notifications->unreadInformationCount($user),
        ]);
    }

    public function actions(User $user, int $limit): Collection
    {
        return $this->remember($user, 'actions:'.$limit, fn (): Collection => $this->actions->actions($user, $limit));
    }

    public function information(User $user): Collection
    {
        return $this->remember($user, 'information', fn (): Collection => $this->notifications->informationNotifications($user, 5));
    }

    public function forget(User $user): void
    {
        foreach (['summary', 'actions:10', 'actions:20', 'information'] as $part) {
            $key = $this->key($user, $part);
            unset($this->memo[$key]);

            try {
                Cache::forget($key);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    private function remember(User $user, string $part, Closure $callback): mixed
    {
        abort_unless($user->isAdmin(), 403);

        $key = $this->key($user, $part);

        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        try {
            $cached = Cache::get($key);
            if ($cached !== null) {
                return $this->memo[$key] = $cached;
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        $value = $callback();

        try {
            Cache::put($key, $value, self::TTL_SECONDS);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $this->memo[$key] = $value;
    }

    private function key(User $user, string $part): string
    {
        $permissions = $user->isSuperAdmin()
            ? ['*']
            : app(AdminMenuPermissions::class)->keys($user->resolvedAdminRole());
        $context = hash('sha256', json_encode([
            $user->role, $user->resolvedAdminRole(), $permissions, request()->root(),
        ], JSON_THROW_ON_ERROR));

        // The user ID is essential: inspection/read badges are not shared by role.
        return 'woms:admin-presentation:v1:'.$user->getKey().':'.$context.':'.$part;
    }
}
