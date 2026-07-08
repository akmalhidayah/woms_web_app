<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationRead;
use App\Support\UserNotificationCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function read(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notification_key' => ['required', 'string', 'max:191'],
            'redirect_url' => ['nullable', 'string', 'max:2048'],
        ]);

        UserNotificationRead::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'notification_key' => $validated['notification_key'],
            ],
            [
                'read_at' => now(),
            ]
        );

        return redirect()->to($this->safeRedirectUrl($request, $validated['redirect_url'] ?? null));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $user = $request->user();
        $now = now();

        $rows = UserNotificationCenter::unreadNotificationKeys($user)
            ->map(fn (string $key): array => [
                'user_id' => $user->id,
                'notification_key' => $key,
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            UserNotificationRead::query()->upsert(
                $rows,
                ['user_id', 'notification_key'],
                ['read_at', 'updated_at']
            );
        }

        return back()->with('status', 'Semua pemberitahuan sudah ditandai dibaca.');
    }

    private function safeRedirectUrl(Request $request, ?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return route('user.dashboard');
        }

        $host = parse_url($url, PHP_URL_HOST);

        if ($host !== null && $host !== $request->getHost()) {
            return route('user.dashboard');
        }

        return $url;
    }
}
