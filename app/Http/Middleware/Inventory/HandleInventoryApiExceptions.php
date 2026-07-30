<?php

namespace App\Http\Middleware\Inventory;

use App\Exceptions\Inventory\InactiveInventoryItemException;
use App\Exceptions\Inventory\InsufficientInventoryStockException;
use App\Exceptions\Inventory\InvalidInventoryActorException;
use App\Exceptions\Inventory\InvalidInventoryRequestTypeException;
use App\Exceptions\Inventory\InvalidStockQuantityException;
use App\Exceptions\Inventory\InventoryIdempotencyConflictException;
use App\Exceptions\Inventory\InventoryStockOverflowException;
use App\Exceptions\Inventory\OpeningBalanceAlreadyExistsException;
use App\Models\Inventory\InventoryUser;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class HandleInventoryApiExceptions
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $exception) {
            return $this->renderException($request, $exception);
        }
    }

    public function renderException(Request $request, Throwable $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return $this->error('Data yang diberikan tidak valid.', 422, $exception->errors());
        }

        if ($exception instanceof AuthenticationException) {
            return $this->error('Token tidak tersedia atau tidak valid.', 401);
        }

        if ($exception instanceof InvalidInventoryActorException || $exception instanceof AuthorizationException) {
            return $this->error('Anda tidak memiliki akses ke resource ini.', 403);
        }

        if ($exception instanceof InsufficientInventoryStockException) {
            return $this->error($exception->getMessage(), 409, [
                'quantity' => ['Jumlah yang diminta melebihi stok tersedia.'],
            ]);
        }

        if ($exception instanceof OpeningBalanceAlreadyExistsException || $exception instanceof InventoryIdempotencyConflictException) {
            return $this->error($exception->getMessage(), 409);
        }

        if (
            $exception instanceof InactiveInventoryItemException
            || $exception instanceof InvalidInventoryRequestTypeException
            || $exception instanceof InvalidStockQuantityException
            || $exception instanceof InventoryStockOverflowException
        ) {
            return $this->error($exception->getMessage(), 422);
        }

        if ($exception instanceof ModelNotFoundException) {
            return $this->error('Resource tidak ditemukan.', 404);
        }

        if ($exception instanceof ThrottleRequestsException) {
            return $this->error('Terlalu banyak permintaan. Silakan coba kembali nanti.', 429)
                ->withHeaders($exception->getHeaders());
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $message = match ($status) {
                404 => 'Resource tidak ditemukan.',
                403 => 'Anda tidak memiliki akses ke resource ini.',
                default => 'Permintaan tidak dapat diproses.',
            };

            return $this->error($message, $status);
        }

        Log::error('Unexpected Inventory API failure.', [
            'exception' => $exception::class,
            'route_name' => $request->route()?->getName(),
            'inventory_user_id' => $request->user() instanceof InventoryUser
                ? $request->user()->getKey()
                : null,
        ]);

        return $this->error('Terjadi kesalahan pada server.', 500);
    }

    private function error(string $message, int $status, ?array $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
