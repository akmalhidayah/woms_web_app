<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

abstract class Controller extends BaseController
{
    /**
     * Execute an action on the controller with centralized logging.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function callAction($method, $parameters)
    {
        $context = [
            'controller' => static::class,
            'action' => $method,
            'route_name' => request()->route()?->getName(),
            'http_method' => request()->method(),
            'url' => request()->fullUrl(),
            'user_id' => request()->user()?->id,
        ];

        try {
            $result = parent::callAction($method, $parameters);

            Log::info('Controller action completed.', [
                ...$context,
                'status_code' => $result instanceof SymfonyResponse ? $result->getStatusCode() : 200,
            ]);

            return $result;
        } catch (Throwable $exception) {
            $statusCode = $this->resolveStatusCode($exception);
            $level = $statusCode >= 500 ? 'error' : 'warning';

            Log::{$level}('Controller action failed.', [
                ...$context,
                'status_code' => $statusCode,
                'error_class' => $exception::class,
                'error_message' => $exception->getMessage(),
                'error_file' => $exception->getFile(),
                'error_line' => $exception->getLine(),
            ]);

            throw $exception;
        }
    }

    private function resolveStatusCode(Throwable $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            $statusCode = (int) $exception->getStatusCode();

            if ($statusCode >= 100 && $statusCode <= 599) {
                return $statusCode;
            }
        }

        if (property_exists($exception, 'status') && is_int($exception->status)) {
            return $exception->status;
        }

        $code = (int) $exception->getCode();

        if ($code >= 100 && $code <= 599) {
            return $code;
        }

        return 500;
    }
}
