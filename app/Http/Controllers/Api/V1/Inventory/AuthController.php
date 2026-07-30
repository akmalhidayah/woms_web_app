<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Inventory\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Inventory\LoginRequest;
use App\Http\Resources\Api\V1\Inventory\InventoryUserResource;
use App\Models\Inventory\InventoryUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $email = Str::lower(trim($request->string('email')->toString()));
        $key = 'inventory-login:'.sha1($email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan login. Silakan coba kembali dalam '.RateLimiter::availableIn($key).' detik.',
            ], 429);
        }

        $user = InventoryUser::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user || ! $user->is_active || $user->trashed() || ! Hash::check($request->string('password'), $user->password)) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'success' => false,
                'message' => 'Email atau password tidak valid.',
            ], 401);
        }

        RateLimiter::clear($key);
        $deviceName = Str::limit(trim(preg_replace('/\s+/', ' ', $request->string('device_name')->toString())), 100, '');
        $tokenName = 'inventory-flutter:'.$deviceName;

        $user->tokens()->where('name', $tokenName)->delete();
        $token = $user->createToken($tokenName, ['inventory-mobile']);
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'must_change_password' => $user->must_change_password,
                'user' => new InventoryUserResource($user->refresh()),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diambil.',
            'data' => new InventoryUserResource($request->user()),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var InventoryUser $user */
        $user = $request->user();

        if (! Hash::check($request->string('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak sesuai.'],
            ]);
        }

        if (Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password baru tidak boleh sama dengan password lama.'],
            ]);
        }

        $currentTokenId = $user->currentAccessToken()?->getKey();
        $user->forceFill([
            'password' => $request->string('password')->toString(),
            'must_change_password' => false,
        ])->save();
        $user->tokens()->whereKeyNot($currentTokenId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
            'data' => new InventoryUserResource($user->refresh()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
            'data' => null,
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Seluruh perangkat berhasil logout.',
            'data' => null,
        ]);
    }
}
