<?php

namespace App\Console\Commands;

use App\Http\Middleware\EnsureOrganizationApiToken;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class ManageOrganizationApiToken extends Command
{
    protected $signature = 'organization:token
        {action : provision, issue, rotate, list, atau revoke}
        {--account-name=Overhaul Organization Integration : Nama service account saat provision}
        {--email= : Email service account baru saat provision}
        {--token-name=overhaul-organization : Nama token saat issue atau rotate}
        {--expires-in-days= : Masa berlaku token dalam hari}
        {--token-id= : ID token lama untuk revoke atau rotate}';

    protected $description = 'Kelola service account dan token Sanctum read-only untuk API organisasi WOMS.';

    public function handle(): int
    {
        $action = Str::lower(trim((string) $this->argument('action')));

        if ($action === 'provision') {
            return $this->provisionServiceAccount();
        }

        if (! in_array($action, ['issue', 'rotate', 'list', 'revoke'], true)) {
            $this->error('Action harus provision, issue, rotate, list, atau revoke.');

            return self::INVALID;
        }

        $serviceAccount = $this->configuredServiceAccount();
        if (! $serviceAccount) {
            return self::FAILURE;
        }

        return match ($action) {
            'issue' => $this->issue($serviceAccount),
            'rotate' => $this->rotate($serviceAccount),
            'list' => $this->listTokens($serviceAccount),
            'revoke' => $this->revoke($serviceAccount),
        };
    }

    private function provisionServiceAccount(): int
    {
        $name = trim((string) $this->option('account-name'));
        $email = Str::lower(trim((string) $this->option('email')));

        if ($name === '' || mb_strlen($name) > 255) {
            $this->error('Nama service account wajib diisi dan maksimal 255 karakter.');

            return self::INVALID;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $this->error('Gunakan --email dengan format email yang valid.');

            return self::INVALID;
        }

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            $this->error('Email tersebut sudah digunakan. Provision tidak mengambil alih akun existing.');

            return self::FAILURE;
        }

        $serviceAccount = User::create([
            'name' => $name,
            'email' => $email,
            'role' => User::ROLE_USER,
            'admin_role' => null,
            'password' => Str::random(80),
        ]);

        $this->info('Service account integrasi berhasil dibuat.');
        $this->table(['Field', 'Nilai'], [
            ['User ID', (string) $serviceAccount->id],
            ['Nama', $serviceAccount->name],
            ['Email', $serviceAccount->email],
            ['Role internal', $serviceAccount->role],
        ]);
        $this->warn('Set ORGANIZATION_API_CLIENT_USER_ID='.$serviceAccount->id.' pada environment server, lalu muat ulang config sebelum menerbitkan token.');

        return self::SUCCESS;
    }

    private function issue(User $serviceAccount): int
    {
        $token = $this->createToken($serviceAccount);
        if (! $token) {
            return self::INVALID;
        }

        $this->displayIssuedToken($token);

        return self::SUCCESS;
    }

    private function rotate(User $serviceAccount): int
    {
        $oldToken = null;
        $oldTokenId = $this->positiveIntegerOption('token-id', required: false);

        if ($this->option('token-id') !== null && $oldTokenId === null) {
            return self::INVALID;
        }

        if ($oldTokenId !== null) {
            $oldToken = $serviceAccount->tokens()->whereKey($oldTokenId)->first();

            if (! $oldToken) {
                $this->error('Token lama tidak ditemukan pada service account terkonfigurasi.');

                return self::FAILURE;
            }
        }

        $newToken = $this->createToken($serviceAccount, $oldToken);
        if (! $newToken) {
            return self::INVALID;
        }

        if ($oldToken) {
            $this->info("Token lama ID {$oldTokenId} telah dicabut.");
        } else {
            $this->warn('Token lama belum dicabut. Perbarui token pada Overhaul, lalu jalankan action revoke untuk token lama.');
        }

        $this->displayIssuedToken($newToken);

        return self::SUCCESS;
    }

    private function listTokens(User $serviceAccount): int
    {
        $tokens = $serviceAccount->tokens()
            ->orderByDesc('id')
            ->get();

        if ($tokens->isEmpty()) {
            $this->info('Belum ada token pada service account ini.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Nama', 'Abilities', 'Kedaluwarsa', 'Terakhir dipakai', 'Dibuat'],
            $tokens->map(fn (PersonalAccessToken $token): array => [
                (string) $token->id,
                (string) $token->name,
                implode(', ', $token->abilities ?? []),
                $token->expires_at ? $token->expires_at->utc()->format('Y-m-d H:i:s').' UTC' : '-',
                $token->last_used_at ? $token->last_used_at->utc()->format('Y-m-d H:i:s').' UTC' : '-',
                $token->created_at ? $token->created_at->utc()->format('Y-m-d H:i:s').' UTC' : '-',
            ])->all(),
        );

        return self::SUCCESS;
    }

    private function revoke(User $serviceAccount): int
    {
        $tokenId = $this->positiveIntegerOption('token-id', required: true);
        if ($tokenId === null) {
            return self::INVALID;
        }

        $token = $serviceAccount->tokens()->whereKey($tokenId)->first();
        if (! $token) {
            $this->error('Token tidak ditemukan pada service account terkonfigurasi.');

            return self::FAILURE;
        }

        $token->delete();
        $this->info("Token ID {$tokenId} berhasil dicabut.");

        return self::SUCCESS;
    }

    private function configuredServiceAccount(): ?User
    {
        $configuredUserId = filter_var(
            config('services.organization_api.client_user_id'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if ($configuredUserId === false) {
            $this->error('ORGANIZATION_API_CLIENT_USER_ID belum dikonfigurasi dengan ID user yang valid.');

            return null;
        }

        $serviceAccount = User::query()->find($configuredUserId);

        if (! $serviceAccount) {
            $this->error('Service account terkonfigurasi tidak ditemukan.');

            return null;
        }

        return $serviceAccount;
    }

    private function createToken(
        User $serviceAccount,
        ?PersonalAccessToken $tokenToRevoke = null,
    ): ?NewAccessToken
    {
        $tokenName = trim((string) $this->option('token-name'));
        if ($tokenName === '' || mb_strlen($tokenName) > 255) {
            $this->error('Nama token wajib diisi dan maksimal 255 karakter.');

            return null;
        }

        $expiresInDays = $this->expiresInDays();
        if ($expiresInDays === null) {
            return null;
        }

        return DB::transaction(function () use ($serviceAccount, $tokenName, $expiresInDays, $tokenToRevoke): NewAccessToken {
            $token = $serviceAccount->createToken(
                $tokenName,
                [EnsureOrganizationApiToken::ABILITY],
                now('UTC')->addDays($expiresInDays),
            );

            $tokenToRevoke?->delete();

            return $token;
        });
    }

    private function expiresInDays(): ?int
    {
        $value = $this->option('expires-in-days');
        $value = $value === null || $value === ''
            ? config('services.organization_api.token_ttl_days', 90)
            : $value;

        $days = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
                'max_range' => 3650,
            ],
        ]);

        if ($days === false) {
            $this->error('Masa berlaku token harus berupa 1 sampai 3650 hari.');

            return null;
        }

        return $days;
    }

    private function positiveIntegerOption(string $name, bool $required): ?int
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            if ($required) {
                $this->error("Gunakan --{$name}=<ID>.");
            }

            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($integer === false) {
            $this->error("--{$name} harus berupa integer positif.");

            return null;
        }

        return $integer;
    }

    private function displayIssuedToken(NewAccessToken $token): void
    {
        $this->info('Token Sanctum berhasil diterbitkan dengan ability organization:read.');
        $this->table(['Field', 'Nilai'], [
            ['Token ID', (string) $token->accessToken->id],
            ['Kedaluwarsa', $token->accessToken->expires_at
                ? $token->accessToken->expires_at->utc()->format('Y-m-d H:i:s').' UTC'
                : '-'],
        ]);
        $this->warn('Salin token plaintext berikut sekarang. Token ini tidak dapat ditampilkan kembali:');
        $this->line($token->plainTextToken);
    }
}
