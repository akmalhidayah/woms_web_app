<?php

namespace App\Services\AppSheet;

use App\Exceptions\AppSheet\GoogleOAuthException;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SensitiveParameter;
use Throwable;

class GoogleOAuthService
{
    public const SCOPE = 'https://www.googleapis.com/auth/spreadsheets.readonly';

    private const TOKEN_PATH = 'appsheet/google/oauth-tokens.enc';

    public function authorizationUrl(string $state): string
    {
        $credentials = $this->credentials();

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $credentials['client_id'],
            'redirect_uri' => $credentials['redirect_uri'],
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeCode(#[SensitiveParameter] string $code): void
    {
        $this->withTokenLock(function () use ($code): void {
            $response = $this->requestTokens([
                ...$this->credentials(),
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

            // Koneksi baru dapat berasal dari akun Google lain: gunakan hanya token grant baru.
            $this->storeTokens($this->tokenPayload($response));
        });
    }

    public function isConnected(): bool
    {
        return $this->withTokenLock(function (): bool {
            $tokens = $this->readTokens();

            return $tokens !== null
                && ($tokens['expires_at'] > now()->timestamp || filled($tokens['refresh_token']));
        });
    }

    /**
     * Access token hanya untuk pemakaian server-side oleh service AppSheet.
     */
    public function accessToken(): string
    {
        return $this->withTokenLock(function (): string {
            $tokens = $this->readTokens();

            if ($tokens === null) {
                throw new GoogleOAuthException('Google belum terhubung. Silakan hubungkan Google terlebih dahulu.');
            }

            if ($tokens['expires_at'] > now()->timestamp + 60) {
                return $tokens['access_token'];
            }

            if (blank($tokens['refresh_token'])) {
                throw new GoogleOAuthException('Koneksi Google perlu diperbarui. Silakan hubungkan Google kembali.');
            }

            $credentials = $this->credentials();

            try {
                $response = $this->requestTokens([
                    'client_id' => $credentials['client_id'],
                    'client_secret' => $credentials['client_secret'],
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $tokens['refresh_token'],
                ]);
            } catch (GoogleOAuthException $exception) {
                if ($exception->requiresReconnect) {
                    $tokens['refresh_token'] = null;
                    $tokens['expires_at'] = 0;
                    $this->storeTokens($tokens);
                }

                throw $exception;
            }

            $tokens = $this->tokenPayload($response, $tokens['refresh_token']);
            $this->storeTokens($tokens);

            return $tokens['access_token'];
        });
    }

    /**
     * @return array<string, string>
     */
    private function credentials(): array
    {
        $credentials = [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect_uri'),
        ];

        foreach ($credentials as $value) {
            if (! is_string($value) || trim($value) === '') {
                throw new GoogleOAuthException('Konfigurasi koneksi Google belum lengkap. Hubungi pengelola aplikasi.');
            }
        }

        return $credentials;
    }

    /**
     * @param  array<string, string>  $payload
     * @return array<string, mixed>
     */
    private function requestTokens(#[SensitiveParameter] array $payload): array
    {
        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(20)
            ->withoutRedirecting()
            ->post('https://oauth2.googleapis.com/token', $payload);

        // Jangan memakai throw(): exception HTTP dapat menyertakan isi respons token.
        if (! $response->successful()) {
            if ($response->json('error') === 'invalid_grant') {
                throw new GoogleOAuthException('Otorisasi Google sudah tidak berlaku. Silakan hubungkan Google kembali.', true);
            }

            throw new GoogleOAuthException('Google belum dapat menyelesaikan koneksi. Silakan coba kembali atau hubungi pengelola aplikasi.');
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new GoogleOAuthException('Respons koneksi Google tidak valid. Silakan coba kembali.');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function tokenPayload(#[SensitiveParameter] array $response, #[SensitiveParameter] ?string $refreshToken = null): array
    {
        $accessToken = $response['access_token'] ?? null;
        $expiresIn = filter_var($response['expires_in'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $scope = $response['scope'] ?? self::SCOPE;
        $tokenType = $response['token_type'] ?? 'Bearer';

        if (! is_string($accessToken) || trim($accessToken) === '' || $expiresIn === false
            || ! is_string($tokenType) || strtolower($tokenType) !== 'bearer') {
            throw new GoogleOAuthException('Respons koneksi Google tidak valid. Silakan coba kembali.');
        }

        if (! is_string($scope) || ! in_array(self::SCOPE, preg_split('/\s+/', trim($scope)), true)) {
            throw new GoogleOAuthException('Izin membaca Google Sheets belum diberikan. Silakan hubungkan Google kembali dan berikan izin tersebut.');
        }

        $returnedRefreshToken = $response['refresh_token'] ?? null;
        if (is_string($returnedRefreshToken) && trim($returnedRefreshToken) !== '') {
            $refreshToken = $returnedRefreshToken;
        }

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $expiresIn,
            'expires_at' => now()->timestamp + $expiresIn,
            'scope' => self::SCOPE,
            'client_id_hash' => hash('sha256', $this->credentials()['client_id']),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readTokens(): ?array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists(self::TOKEN_PATH)) {
            return null;
        }

        $encrypted = $disk->get(self::TOKEN_PATH);
        if (! is_string($encrypted) || $encrypted === '') {
            throw new GoogleOAuthException('Koneksi Google tersimpan tidak dapat dibaca. Silakan hubungkan Google kembali.');
        }

        $tokens = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($tokens) || ! is_string($tokens['access_token'] ?? null)
            || $tokens['access_token'] === '' || ! is_int($tokens['expires_at'] ?? null)
            || ! is_int($tokens['expires_in'] ?? null)
            || (! is_null($tokens['refresh_token'] ?? null) && ! is_string($tokens['refresh_token']))) {
            throw new GoogleOAuthException('Koneksi Google tersimpan tidak valid. Silakan hubungkan Google kembali.');
        }

        if (($tokens['client_id_hash'] ?? null) !== hash('sha256', $this->credentials()['client_id'])) {
            return null;
        }

        $tokens['refresh_token'] ??= null;

        return $tokens;
    }

    /**
     * @param  array<string, mixed>  $tokens
     */
    private function storeTokens(#[SensitiveParameter] array $tokens): void
    {
        $encrypted = Crypt::encryptString(json_encode($tokens, JSON_THROW_ON_ERROR));
        $disk = Storage::disk('local');
        $temporaryPath = self::TOKEN_PATH.'.'.Str::uuid().'.tmp';

        try {
            if (! $disk->put($temporaryPath, $encrypted, 'private')
                || ! $disk->move($temporaryPath, self::TOKEN_PATH)) {
                throw new GoogleOAuthException('Koneksi Google gagal disimpan di server. Silakan coba kembali.');
            }
        } finally {
            if ($disk->exists($temporaryPath) && ! $disk->delete($temporaryPath)) {
                throw new GoogleOAuthException('Pembersihan file sementara koneksi Google gagal. Hubungi pengelola aplikasi.');
            }
        }
    }

    private function withTokenLock(Closure $callback): mixed
    {
        try {
            return Cache::store('file')->lock('appsheet:google:tokens', 60)->block(5, $callback);
        } catch (GoogleOAuthException $exception) {
            throw $exception;
        } catch (LockTimeoutException) {
            throw new GoogleOAuthException('Koneksi Google sedang diproses. Silakan coba kembali sebentar lagi.');
        } catch (Throwable $exception) {
            Log::warning('AppSheet Google OAuth operation failed.', ['exception_type' => $exception::class]);

            throw new GoogleOAuthException('Koneksi Google tidak dapat diproses. Silakan coba kembali atau hubungi pengelola aplikasi.');
        }
    }
}
