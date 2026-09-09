<?php

namespace App\Http\Controllers\Admin\AppSheet;

use App\Exceptions\AppSheet\GoogleOAuthException;
use App\Http\Controllers\Controller;
use App\Services\AppSheet\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GoogleOAuthController extends Controller
{
    private const HISTORY_ROUTE = 'admin.appsheet.history-consumable.index';

    private const STOCK_ROUTE = 'admin.appsheet.stock-consumable.index';

    public function connect(Request $request, GoogleOAuthService $google): RedirectResponse
    {
        $returnRoute = $request->query('return_to') === 'stock' ? self::STOCK_ROUTE : self::HISTORY_ROUTE;

        try {
            $state = Str::random(64);
            $canReplaceConnection = $request->user()->isSuperAdmin();
            $authorizationUrl = $google->authorizationUrl($state, $canReplaceConnection);
            $stored = Cache::store('file')->put($this->stateKey($state), [
                'binding' => $this->sessionBinding($request),
                'return_route' => $returnRoute,
                'can_replace_connection' => $canReplaceConnection,
            ], 600);

            if (! $stored) {
                throw new GoogleOAuthException('Sesi koneksi Google gagal disiapkan. Silakan coba kembali.');
            }

            return $this->privateRedirect(redirect()->away($authorizationUrl));
        } catch (GoogleOAuthException $exception) {
            return $this->backToPage($returnRoute, 'appsheet_google_error', $exception->getMessage());
        } catch (Throwable $exception) {
            return $this->unexpectedFailure($returnRoute, $exception);
        }
    }

    public function callback(Request $request, GoogleOAuthService $google): RedirectResponse
    {
        $returnRoute = self::HISTORY_ROUTE;

        try {
            $state = $request->query('state');
            if (! is_string($state) || ! preg_match('/\A[A-Za-z0-9]{64}\z/', $state)) {
                throw new GoogleOAuthException('Sesi koneksi Google tidak valid atau kedaluwarsa. Silakan hubungkan Google kembali.');
            }

            $cache = Cache::store('file');
            $stateKey = $this->stateKey($state);
            $pending = $cache->lock($stateKey.':lock', 10)->block(3, function () use ($cache, $stateKey, $request): array {
                $pending = $cache->get($stateKey);

                if (! is_array($pending) || ! is_string($pending['binding'] ?? null)
                    || ! hash_equals($pending['binding'], $this->sessionBinding($request))) {
                    throw new GoogleOAuthException('Sesi koneksi Google tidak valid atau kedaluwarsa. Silakan hubungkan Google kembali.');
                }

                // State sekali pakai: callback paralel atau replay tidak boleh menukar code lagi.
                if (! $cache->forget($stateKey)) {
                    throw new GoogleOAuthException('Sesi koneksi Google tidak dapat divalidasi. Silakan hubungkan Google kembali.');
                }

                return $pending;
            });

            if (in_array($pending['return_route'] ?? null, [self::HISTORY_ROUTE, self::STOCK_ROUTE], true)) {
                $returnRoute = $pending['return_route'];
            }

            if ($request->query->has('error')) {
                throw new GoogleOAuthException('Koneksi Google dibatalkan atau izin belum diberikan. Silakan hubungkan Google kembali.');
            }

            $code = $request->query('code');
            if (! is_string($code) || trim($code) === '') {
                throw new GoogleOAuthException('Kode otorisasi Google tidak tersedia. Silakan hubungkan Google kembali.');
            }

            // Hak penggantian berasal dari Admin login, bukan query OAuth/browser.
            $google->exchangeCode($code, $request->user()->isSuperAdmin()
                && ($pending['can_replace_connection'] ?? false) === true);

            return $this->backToPage($returnRoute, 'appsheet_google_success', 'Google berhasil terhubung.');
        } catch (GoogleOAuthException $exception) {
            return $this->backToPage($returnRoute, 'appsheet_google_error', $exception->getMessage());
        } catch (Throwable $exception) {
            return $this->unexpectedFailure($returnRoute, $exception);
        }
    }

    private function stateKey(string $state): string
    {
        return 'appsheet:google:state:'.hash('sha256', $state);
    }

    private function sessionBinding(Request $request): string
    {
        return hash('sha256', $request->session()->getId().'|'.$request->user()->getAuthIdentifier());
    }

    private function backToPage(string $route, string $flashKey, string $message): RedirectResponse
    {
        return $this->privateRedirect(redirect()->route($route)->with($flashKey, $message));
    }

    private function privateRedirect(RedirectResponse $response): RedirectResponse
    {
        return $response->withHeaders([
            'Cache-Control' => 'no-store, private',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    private function unexpectedFailure(string $returnRoute, Throwable $exception): RedirectResponse
    {
        // Jangan mencatat request callback, code, body HTTP, maupun exception trace.
        Log::warning('AppSheet Google OAuth callback/connect failed.', ['exception_type' => $exception::class]);

        return $this->backToPage($returnRoute, 'appsheet_google_error', 'Koneksi Google tidak dapat diproses. Silakan coba kembali atau hubungi pengelola aplikasi.');
    }
}
