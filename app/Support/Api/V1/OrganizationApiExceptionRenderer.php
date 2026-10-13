<?php

namespace App\Support\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

final class OrganizationApiExceptionRenderer
{
    public function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('api/v1/organization') && ! $request->is('api/v1/organization/*')) {
            return null;
        }

        [$status, $message, $errors] = match (true) {
            $exception instanceof ValidationException => [
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Validation failed',
                $exception->errors(),
            ],
            $exception instanceof AuthenticationException => [
                Response::HTTP_UNAUTHORIZED,
                'Unauthenticated',
                null,
            ],
            $exception instanceof AuthorizationException => [
                Response::HTTP_FORBIDDEN,
                'Forbidden',
                null,
            ],
            $exception instanceof ModelNotFoundException,
            $exception instanceof NotFoundHttpException => [
                Response::HTTP_NOT_FOUND,
                'Not found',
                null,
            ],
            $exception instanceof MethodNotAllowedHttpException => [
                Response::HTTP_METHOD_NOT_ALLOWED,
                'Method not allowed',
                null,
            ],
            $exception instanceof TooManyRequestsHttpException => [
                Response::HTTP_TOO_MANY_REQUESTS,
                'Too many requests',
                null,
            ],
            $exception instanceof HttpExceptionInterface => [
                $exception->getStatusCode(),
                Response::$statusTexts[$exception->getStatusCode()] ?? 'Request failed',
                null,
            ],
            default => [
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Internal server error',
                null,
            ],
        };

        $response = response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);

        if ($exception instanceof HttpExceptionInterface) {
            $response->withHeaders($exception->getHeaders());
        }

        return $response;
    }
}
