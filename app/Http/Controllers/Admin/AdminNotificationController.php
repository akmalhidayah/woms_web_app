<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotificationRead;
use App\Support\AdminActionCenter;
use App\Support\AdminNotificationCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function __construct(
        private readonly AdminNotificationCenter $notificationCenter,
        private readonly AdminActionCenter $actionCenter,
    ) {}

    public function read(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notification_key' => ['required', 'string', 'max:191'],
            'redirect_url' => ['nullable', 'string', 'max:2048'],
        ]);

        AdminNotificationRead::query()->updateOrCreate(
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

        $rows = $this->notificationCenter->unreadInformationKeys($user)
            ->map(fn (string $key): array => [
                'user_id' => $user->id,
                'notification_key' => $key,
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            AdminNotificationRead::query()->upsert(
                $rows,
                ['user_id', 'notification_key'],
                ['read_at', 'updated_at']
            );
        }

        return back()->with('status', 'Semua pemberitahuan sudah ditandai dibaca.');
    }

    public function actionFeed(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'pending_count' => $this->actionCenter->pendingActionCount($user),
            'actions' => $this->actionCenter
                ->actions($user, 20)
                ->map(fn (array $action): array => [
                    'key' => $action['key'],
                    'title' => $action['title'],
                    'message' => $action['message'],
                    'url' => $action['url'],
                    'overdue_level' => $action['overdue_level'],
                ])
                ->values()
                ->all(),
        ]);
    }

    private function safeRedirectUrl(Request $request, ?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return route('admin.dashboard');
        }

        $host = parse_url($url, PHP_URL_HOST);

        if ($host !== null && $host !== $request->getHost()) {
            return route('admin.dashboard');
        }

        return $url;
    }
}
