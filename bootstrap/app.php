<?php

use App\Console\Commands\ReprocessHppSignatureImages;
use App\Console\Commands\RunMaintenanceScanCommand;
use App\Console\Commands\SyncBastSmPengendali;
use App\Http\Middleware\EnsureAdminHasSubrole;
use App\Http\Middleware\EnsureAdminMenuAccess;
use App\Http\Middleware\EnsurePkmPanelAccess;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\Inventory\HandleInventoryApiExceptions;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        ReprocessHppSignatureImages::class,
        RunMaintenanceScanCommand::class,
        SyncBastSmPengendali::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'admin_role' => EnsureAdminHasSubrole::class,
            'admin_menu' => EnsureAdminMenuAccess::class,
            'pkm_panel' => EnsurePkmPanelAccess::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->user()) {
                return route('dashboard');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/v1/inventory*')) {
                return null;
            }

            return app(HandleInventoryApiExceptions::class)->renderException($request, $exception);
        });
    })->create();
