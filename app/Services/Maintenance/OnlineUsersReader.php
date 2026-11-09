<?php

namespace App\Services\Maintenance;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Membaca daftar pengguna yang aktif dari tabel sesi. Read-only, tanpa side effect.
 *
 * "Online" = sesi dengan last_activity dalam jendela waktu terakhir. last_activity hanya
 * diperbarui saat user memuat halaman, bukan saat user hanya membaca halaman yang sudah terbuka.
 * IP address dan payload sesi sengaja tidak dibaca.
 */
class OnlineUsersReader
{
    /**
     * @return array{
     *     available: bool,
     *     window_minutes: int,
     *     users: list<array{name: string, role_label: string, last_activity: Carbon, devices: list<string>, session_count: int}>
     * }
     */
    public function read(): array
    {
        $windowMinutes = max(1, (int) config('maintenance.online_window_minutes', 5));

        if (config('session.driver') !== 'database') {
            return ['available' => false, 'window_minutes' => $windowMinutes, 'users' => []];
        }

        $sessions = DB::connection(config('session.connection'))
            ->table((string) config('session.table', 'sessions'))
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes($windowMinutes)->getTimestamp())
            ->get(['user_id', 'user_agent', 'last_activity'])
            ->groupBy('user_id');

        if ($sessions->isEmpty()) {
            return ['available' => true, 'window_minutes' => $windowMinutes, 'users' => []];
        }

        $users = User::query()
            ->whereIn('id', $sessions->keys()->all())
            ->get(['id', 'name', 'role', 'admin_role'])
            ->keyBy('id');

        $rows = $sessions
            ->map(function ($userSessions, $userId) use ($users): ?array {
                $user = $users->get($userId);

                if (! $user) {
                    return null;
                }

                return [
                    'name' => (string) $user->name,
                    'role_label' => $this->roleLabel($user),
                    'last_activity' => Carbon::createFromTimestamp((int) $userSessions->max('last_activity')),
                    'devices' => $userSessions
                        ->map(fn ($session): string => $this->deviceLabel((string) $session->user_agent))
                        ->unique()
                        ->values()
                        ->all(),
                    'session_count' => $userSessions->count(),
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $row) => $row['last_activity']->getTimestamp())
            ->values()
            ->all();

        return ['available' => true, 'window_minutes' => $windowMinutes, 'users' => $rows];
    }

    private function roleLabel(User $user): string
    {
        if ($user->role === User::ROLE_ADMIN) {
            return User::adminRoleOptions()[$user->admin_role] ?? 'Admin';
        }

        return User::roleLabels()[$user->role] ?? (string) $user->role;
    }

    private function deviceLabel(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') || str_contains($userAgent, 'CriOS/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Browser lain',
        };

        $platform = match (true) {
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Perangkat lain',
        };

        return $browser.' · '.$platform;
    }
}
