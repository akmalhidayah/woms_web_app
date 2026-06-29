<?php

namespace App\Http\Controllers\Pkm;

use App\Http\Controllers\Controller;
use App\Models\PkmNotificationRead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PkmNotificationController extends Controller
{
    public function read(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notification_key' => ['required', 'string', 'max:191'],
            'redirect_url' => ['nullable', 'string', 'max:2048'],
        ]);

        PkmNotificationRead::query()->updateOrCreate(
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

    private function safeRedirectUrl(Request $request, ?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return route('pkm.dashboard');
        }

        $host = parse_url($url, PHP_URL_HOST);

        if ($host !== null && $host !== $request->getHost()) {
            return route('pkm.dashboard');
        }

        return $url;
    }
}
