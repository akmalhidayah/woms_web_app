<?php

namespace Tests\Unit\AppSheet;

use App\Exceptions\AppSheet\GoogleOAuthException;
use App\Services\AppSheet\GoogleOAuthService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GoogleOAuthServiceTest extends TestCase
{
    private const LEGACY_SHEETS_READ_ONLY_SCOPE = 'https://www.googleapis.com/auth/spreadsheets.readonly';

    private const TOKEN_PATH = 'appsheet/google/oauth-tokens.enc';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.stores.file' => ['driver' => 'array'],
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect_uri' => 'https://woms.test/admin/appsheet/google/callback',
        ]);

        Storage::fake('local');
        Http::preventStrayRequests();
    }

    public function test_authorization_url_requests_sheets_write_and_drive_read_only_scopes(): void
    {
        $query = parse_url((new GoogleOAuthService)->authorizationUrl('oauth-state'), PHP_URL_QUERY);
        parse_str(is_string($query) ? $query : '', $parameters);

        self::assertSame(implode(' ', GoogleOAuthService::SCOPES), $parameters['scope']);
        self::assertSame([
            GoogleOAuthService::SHEETS_SCOPE,
            GoogleOAuthService::DRIVE_SCOPE,
        ], preg_split('/\s+/', $parameters['scope'], -1, PREG_SPLIT_NO_EMPTY));
        self::assertSame('https://woms.test/admin/appsheet/google/callback', $parameters['redirect_uri']);
        self::assertSame('https://www.googleapis.com/auth/spreadsheets', GoogleOAuthService::SHEETS_SCOPE);
        self::assertSame('https://www.googleapis.com/auth/drive.readonly', GoogleOAuthService::DRIVE_SCOPE);
    }

    public function test_authorization_code_response_with_both_scopes_is_accepted_and_stored_encrypted(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response($this->tokenResponse(
                GoogleOAuthService::DRIVE_SCOPE."\n".GoogleOAuthService::SHEETS_SCOPE,
            )),
        ]);

        $google = new GoogleOAuthService;
        $google->exchangeCode('authorization-code');

        $encrypted = Storage::disk('local')->get(self::TOKEN_PATH);
        self::assertStringNotContainsString('new-access-token', $encrypted);
        self::assertStringNotContainsString('new-refresh-token', $encrypted);

        $tokens = $this->storedTokens();
        self::assertSame('new-access-token', $tokens['access_token']);
        self::assertSame('new-refresh-token', $tokens['refresh_token']);
        self::assertSame(implode(' ', GoogleOAuthService::SCOPES), $tokens['scope']);
        self::assertTrue($google->hasDriveReadScope());
        self::assertTrue($google->hasSheetsWriteScope());
    }

    public function test_authorization_code_response_without_drive_scope_is_rejected(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response($this->tokenResponse(GoogleOAuthService::SHEETS_SCOPE)),
        ]);

        $this->expectException(GoogleOAuthException::class);
        $this->expectExceptionMessage('Izin membaca Google Drive belum diberikan. Silakan hubungkan Google kembali dan berikan izin tersebut.');

        (new GoogleOAuthService)->exchangeCode('authorization-code');
    }

    public function test_authorization_code_response_without_sheets_scope_is_rejected(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response($this->tokenResponse(GoogleOAuthService::DRIVE_SCOPE)),
        ]);

        $this->expectException(GoogleOAuthException::class);
        $this->expectExceptionMessage('Izin Google Sheets belum diberikan. Silakan hubungkan Google kembali dan berikan izin tersebut.');

        (new GoogleOAuthService)->exchangeCode('authorization-code');
    }

    public function test_oauth_failure_does_not_expose_secrets_in_exception_or_log(): void
    {
        Log::spy();
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'error' => 'server_error',
                'error_description' => 'sensitive-access-token sensitive-refresh-token sensitive-response-body',
            ], 500),
        ]);

        try {
            (new GoogleOAuthService)->exchangeCode('sensitive-authorization-code');
            self::fail('Expected a safe OAuth exception.');
        } catch (GoogleOAuthException $exception) {
            self::assertStringNotContainsString('sensitive-authorization-code', $exception->getMessage());
            self::assertStringNotContainsString('sensitive-access-token', $exception->getMessage());
            self::assertStringNotContainsString('sensitive-refresh-token', $exception->getMessage());
            self::assertStringNotContainsString('sensitive-response-body', $exception->getMessage());
        }

        Log::shouldNotHaveReceived('warning');
    }

    public function test_refresh_preserves_refresh_token_and_granted_scopes_when_google_omits_scope(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::sequence()
                ->push($this->tokenResponse(implode(' ', GoogleOAuthService::SCOPES), 120))
                ->push([
                    'access_token' => 'refreshed-access-token',
                    'expires_in' => 3600,
                    'token_type' => 'Bearer',
                ]),
        ]);

        $google = new GoogleOAuthService;
        $google->exchangeCode('authorization-code');
        $this->travel(61)->seconds();

        self::assertSame('refreshed-access-token', $google->accessToken());

        $tokens = $this->storedTokens();
        self::assertSame('new-refresh-token', $tokens['refresh_token']);
        self::assertSame(implode(' ', GoogleOAuthService::SCOPES), $tokens['scope']);
        Http::assertSentCount(2);
    }

    public function test_unexpired_legacy_sheets_only_token_remains_readable_until_reconnect(): void
    {
        $this->storeLegacyTokens([
            'access_token' => 'legacy-access-token',
            'refresh_token' => 'legacy-refresh-token',
            'expires_in' => 3600,
            'expires_at' => now()->addHour()->timestamp,
            'scope' => self::LEGACY_SHEETS_READ_ONLY_SCOPE,
            'client_id_hash' => hash('sha256', 'google-client-id'),
        ]);

        $google = new GoogleOAuthService;

        self::assertTrue($google->isConnected());
        self::assertFalse($google->hasDriveReadScope());
        self::assertFalse($google->hasSheetsWriteScope());
        self::assertSame('legacy-access-token', $google->accessToken());
        Http::assertNothingSent();
    }

    public function test_expired_legacy_read_scope_can_refresh_but_still_cannot_write(): void
    {
        $this->storeLegacyTokens([
            'access_token' => 'expired-legacy-access-token',
            'refresh_token' => 'legacy-refresh-token',
            'expires_in' => 3600,
            'expires_at' => now()->subMinute()->timestamp,
            'scope' => self::LEGACY_SHEETS_READ_ONLY_SCOPE,
            'client_id_hash' => hash('sha256', 'google-client-id'),
        ]);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'refreshed-legacy-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
        ]);

        $google = new GoogleOAuthService;

        self::assertSame('refreshed-legacy-access-token', $google->accessToken());
        self::assertFalse($google->hasSheetsWriteScope());
        self::assertSame(self::LEGACY_SHEETS_READ_ONLY_SCOPE, $this->storedTokens()['scope']);
        Http::assertSentCount(1);
    }

    /**
     * @return array<string, mixed>
     */
    private function tokenResponse(string $scope, int $expiresIn = 3600): array
    {
        return [
            'access_token' => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
            'expires_in' => $expiresIn,
            'scope' => $scope,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storedTokens(): array
    {
        return json_decode(
            Crypt::decryptString(Storage::disk('local')->get(self::TOKEN_PATH)),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @param  array<string, mixed>  $tokens
     */
    private function storeLegacyTokens(array $tokens): void
    {
        Storage::disk('local')->put(
            self::TOKEN_PATH,
            Crypt::encryptString(json_encode($tokens, JSON_THROW_ON_ERROR)),
        );
    }
}
